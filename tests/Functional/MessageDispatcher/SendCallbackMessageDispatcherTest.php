<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Event\CallbackEventInterface;
use App\Event\CallbackHttpErrorEvent;
use App\Event\JobCompleteEvent;
use App\Event\JobTimeoutEvent;
use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Message\SendCallbackMessage;
use App\MessageDispatcher\SendCallbackMessageDispatcher;
use App\Model\BackoffStrategy\ExponentialBackoffStrategy;
use App\Model\Callback\CompileFailureCallback;
use App\Model\Callback\DelayedCallback;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Entity\MockTest;
use App\Tests\Model\TestCallback;
use App\Tests\Services\Asserter\MessengerAsserter;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\CallbackRepository;
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
    private CallbackRepository $callbackRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider dispatchForCallbackEventDataProvider
     *
     * @param CallbackInterface $callback
     * @param array<string, array<int, StampInterface>> $expectedEnvelopeContainsStampCollections
     */
    public function testDispatchForCallbackEvent(
        CallbackInterface $callback,
        ?string $expectedEnvelopeNotContainsStampsOfType,
        array $expectedEnvelopeContainsStampCollections
    ): void {
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

    /**
     * @return array[]
     */
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
     *
     * @param array<mixed> $expectedCallbackPayload
     */
    public function testSubscribesToEvent(
        Event $event,
        string $expectedCallbackType,
        array $expectedCallbackPayload
    ): void {
        $this->messengerAsserter->assertQueueIsEmpty();

        $this->eventDispatcher->dispatch($event);

        $this->messengerAsserter->assertQueueCount(1);

        $envelope = $this->messengerAsserter->getEnvelopeAtPosition(0);
        $message = $envelope->getMessage();
        self::assertInstanceOf(SendCallbackMessage::class, $message);

        if ($message instanceof SendCallbackMessage) {
            $callback = $this->callbackRepository->find($message->getCallbackId());
            self::assertInstanceOf(CallbackInterface::class, $callback);

            if ($callback instanceof CallbackInterface) {
                self::assertSame($expectedCallbackType, $callback->getType());
                self::assertSame($expectedCallbackPayload, $callback->getPayload());
            }
        }
    }

    /**
     * @return array[]
     */
    public function subscribesToEventDataProvider(): array
    {
        $httpExceptionEventCallback = CallbackEntity::create(
            CallbackInterface::TYPE_COMPILE_FAILURE,
            [
                'http-exception-event-key' => 'value',
            ]
        );

        $sourceCompileFailureEventOutput = \Mockery::mock(ErrorOutputInterface::class);
        $sourceCompileFailureEventOutput
            ->shouldReceive('getData')
            ->andReturn([
                'compile-failure-key' => 'value',
            ]);

        return [
            'http exception' => [
                'event' => new CallbackHttpErrorEvent($httpExceptionEventCallback, new Response(503)),
                'expectedCallbackType' => CallbackInterface::TYPE_COMPILE_FAILURE,
                'expectedCallbackPayload' => [
                    'http-exception-event-key' => 'value',
                ],
            ],
            SourceCompileFailureEvent::class => [
                'event' => new SourceCompileFailureEvent(
                    '/app/source/Test/test.yml',
                    $sourceCompileFailureEventOutput,
                    new CompileFailureCallback($sourceCompileFailureEventOutput)
                ),
                'expectedCallbackType' => CallbackInterface::TYPE_COMPILE_FAILURE,
                'expectedCallbackPayload' => [
                    'compile-failure-key' => 'value',
                ],
            ],
            TestExecuteDocumentReceivedEvent::class => [
                'event' => new TestExecuteDocumentReceivedEvent(
                    (new MockTest())->getMock(),
                    new Document('document-key: value')
                ),
                'expectedCallbackType' => CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED,
                'expectedCallbackPayload' => [
                    'document-key' => 'value',
                ],
            ],
            JobCompleteEvent::class => [
                'event' => new JobCompleteEvent(),
                'expectedCallbackType' => CallbackInterface::TYPE_JOB_COMPLETE,
                'expectedCallbackPayload' => [],
            ],
            JobTimeoutEvent::class => [
                'event' => new JobTimeoutEvent(10),
                'expectedCallbackType' => CallbackInterface::TYPE_JOB_TIMEOUT,
                'expectedCallbackPayload' => [],
            ],
        ];
    }
}
