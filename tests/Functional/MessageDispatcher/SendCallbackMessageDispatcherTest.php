<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Event\CallbackEventInterface;
use App\Event\CallbackHttpErrorEvent;
use App\Event\JobTimeoutEvent;
use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Message\SendCallbackMessage;
use App\MessageDispatcher\SendCallbackMessageDispatcher;
use App\Model\BackoffStrategy\ExponentialBackoffStrategy;
use App\Model\Callback\CompileFailureCallback;
use App\Model\Callback\DelayedCallback;
use App\Model\Callback\ExecuteDocumentReceivedCallback;
use App\Model\Callback\JobTimeoutCallback;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Entity\MockTest;
use App\Tests\Model\Entity\Callback\TestCallbackEntity;
use App\Tests\Model\TestCallback;
use App\Tests\Services\Asserter\MessengerAsserter;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\YamlDocument\Document;

class SendCallbackMessageDispatcherTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private SendCallbackMessageDispatcher $messageDispatcher;
    private EventDispatcherInterface $eventDispatcher;
    private MessengerAsserter $messengerAsserter;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider dispatchForCallbackEventDataProvider
     *
     * @param CallbackInterface $callback
     * @param string|null $expectedEnvelopeNotContainsStampsOfType
     * @param array<string, array<int, StampInterface>> $expectedEnvelopeContainsStampCollections
     */
    public function testDispatchForCallbackEvent(
        CallbackInterface $callback,
        ?string $expectedEnvelopeNotContainsStampsOfType,
        array $expectedEnvelopeContainsStampCollections
    ) {
        $event = \Mockery::mock(CallbackEventInterface::class);
        $event
            ->shouldReceive('getCallback')
            ->andReturn($callback);

        $this->messageDispatcher->dispatchForCallbackEvent($event);

        $callbackRepository = $this->entityManager->getRepository(CallbackEntity::class);
        $callbacks = $callbackRepository->findAll();
        $callback = array_pop($callbacks);

        self::assertInstanceOf(CallbackInterface::class, $callback);

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, new SendCallbackMessage((int) $callback->getId()));

        $envelope = $this->messengerAsserter->getEnvelopeAtPosition(0);

        if (is_string($expectedEnvelopeNotContainsStampsOfType)) {
            $this->messengerAsserter->assertEnvelopeNotContainsStampsOfType(
                $envelope,
                $expectedEnvelopeNotContainsStampsOfType
            );
        }

        $this->messengerAsserter->assertEnvelopeContainsStampCollections(
            $envelope,
            $expectedEnvelopeContainsStampCollections
        );
    }

    public function dispatchForCallbackEventDataProvider(): array
    {
        $nonDelayedCallback = new TestCallback();
        $delayedCallbackRetryCount1 = new DelayedCallback(
            (new TestCallback())
                ->withRetryCount(1),
            new ExponentialBackoffStrategy()
        );

        return [
            'non-delayed' => [
                'callback' => $nonDelayedCallback,
                'expectedEnvelopeNotContainsStampsOfType' => DelayStamp::class,
                'expectedEnvelopeContainsStampCollections' => [],
            ],
            'delayed, retry count 1' => [
                'callback' => $delayedCallbackRetryCount1,
                'expectedEnvelopeNotContainsStampsOfType' => null,
                'expectedEnvelopeContainsStampCollections' => [
                    DelayStamp::class => [
                        new DelayStamp(1000),
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider subscribesToEventDataProvider
     */
    public function testSubscribesToEvent(
        CallbackEventInterface $event,
        CallbackInterface $expectedQueuedMessageCallback
    ) {
        $callback = $event->getCallback();
        self::assertSame(CallbackInterface::STATE_AWAITING, $callback->getState());
        $this->messengerAsserter->assertQueueIsEmpty();

        $this->eventDispatcher->dispatch($event);

        self::assertSame(CallbackInterface::STATE_QUEUED, $callback->getState());

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            new SendCallbackMessage((int) $expectedQueuedMessageCallback->getId())
        );
    }

    public function subscribesToEventDataProvider(): array
    {
        $httpExceptionEventCallback = TestCallbackEntity::createWithUniquePayload();
        $httpResponseExceptionCallback = TestCallbackEntity::createWithUniquePayload();

        $sourceCompileFailureEventOutput = \Mockery::mock(ErrorOutputInterface::class);
        $sourceCompileFailureEventOutput
            ->shouldReceive('getData')
            ->andReturn([
                'unique' => md5(random_bytes(16)),
            ]);

        $sourceCompileFailureEventCallback = new CompileFailureCallback($sourceCompileFailureEventOutput);

        $document = new Document('data');
        $testExecuteDocumentReceivedEventCallback = new ExecuteDocumentReceivedCallback($document);

        $jobTimeoutEvent = new JobTimeoutEvent(10);

        $jobTimeoutCallback = new JobTimeoutCallback(10);
        $jobTimeoutCallback->setState(CallbackInterface::STATE_QUEUED);

        return [
            'http non-success response' => [
                'event' => new CallbackHttpErrorEvent(
                    $httpExceptionEventCallback,
                    \Mockery::mock(ConnectException::class)
                ),
                'expectedQueuedMessageCallback' => $httpExceptionEventCallback,
            ],
            'http exception' => [
                'event' => new CallbackHttpErrorEvent($httpResponseExceptionCallback, new Response(503)),
                'expectedQueuedMessage' => $httpResponseExceptionCallback,
            ],
            SourceCompileFailureEvent::class => [
                'event' => new SourceCompileFailureEvent(
                    '/app/source/Test/test.yml',
                    $sourceCompileFailureEventOutput,
                    $sourceCompileFailureEventCallback
                ),
                'expectedQueuedMessageCallback' => $sourceCompileFailureEventCallback,
            ],
            TestExecuteDocumentReceivedEvent::class => [
                'event' => new TestExecuteDocumentReceivedEvent(
                    (new MockTest())->getMock(),
                    $document,
                    $testExecuteDocumentReceivedEventCallback
                ),
                'expectedQueuedMessageCallback' => $testExecuteDocumentReceivedEventCallback,
            ],
            JobTimeoutEvent::class => [
                'event' => $jobTimeoutEvent,
                'expectedQueuedMessageCallback' => $jobTimeoutEvent->getCallback(),
            ],
        ];
    }
}
