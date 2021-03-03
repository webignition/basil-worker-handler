<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Event\CallbackHttpErrorEvent;
use App\Event\CompilationCompletedEvent;
use App\Event\ExecutionStartedEvent;
use App\Event\JobCompletedEvent;
use App\Event\JobTimeoutEvent;
use App\Event\SourceCompilation\SourceCompilationFailedEvent;
use App\Event\SourceCompilation\SourceCompilationPassedEvent;
use App\Event\SourceCompilation\SourceCompilationStartedEvent;
use App\Event\TestStartedEvent;
use App\Event\TestStepFailedEvent;
use App\Event\TestStepPassedEvent;
use App\Message\SendCallbackMessage;
use App\MessageDispatcher\SendCallbackMessageDispatcher;
use App\Services\ExecutionWorkflowHandler;
use App\Services\TestFactory;
use App\Services\TestStateMutator;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Entity\MockTest;
use App\Tests\Mock\MockSuiteManifest;
use App\Tests\Services\Asserter\MessengerAsserter;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
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
    private TestStateMutator $testStateMutator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $this->eventDispatcher->removeListener(
            TestStepFailedEvent::class,
            [
                $this->testStateMutator,
                'setFailedFromTestStepFailedEvent'
            ]
        );

        $testFactory = self::$container->get(TestFactory::class);
        if ($testFactory instanceof TestFactory) {
            $this->eventDispatcher->removeListener(
                SourceCompilationPassedEvent::class,
                [
                    $testFactory,
                    'createFromSourceCompileSuccessEvent'
                ]
            );
        }

        $executionWorkflowHandler = self::$container->get(ExecutionWorkflowHandler::class);
        if ($executionWorkflowHandler instanceof ExecutionWorkflowHandler) {
            $this->eventDispatcher->removeListener(
                SourceCompilationPassedEvent::class,
                [
                    $executionWorkflowHandler,
                    'dispatchExecutionStartedEvent'
                ]
            );
        }
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
            CallbackInterface::TYPE_COMPILATION_FAILED,
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
            CallbackHttpErrorEvent::class => [
                'event' => new CallbackHttpErrorEvent($httpExceptionEventCallback, new Response(503)),
                'expectedCallbackType' => CallbackInterface::TYPE_COMPILATION_FAILED,
                'expectedCallbackPayload' => [
                    'http-exception-event-key' => 'value',
                ],
            ],
            SourceCompilationStartedEvent::class => [
                'event' => new SourceCompilationStartedEvent('/app/source/Test/test.yml'),
                'expectedCallbackType' => CallbackInterface::TYPE_COMPILATION_STARTED,
                'expectedCallbackPayload' => [
                    'source' => '/app/source/Test/test.yml',
                ],
            ],
            SourceCompilationPassedEvent::class => [
                'event' => new SourceCompilationPassedEvent(
                    '/app/source/Test/test.yml',
                    (new MockSuiteManifest())->getMock()
                ),
                'expectedCallbackType' => CallbackInterface::TYPE_COMPILATION_PASSED,
                'expectedCallbackPayload' => [
                    'source' => '/app/source/Test/test.yml',
                ],
            ],
            SourceCompilationFailedEvent::class => [
                'event' => new SourceCompilationFailedEvent(
                    '/app/source/Test/test.yml',
                    $sourceCompileFailureEventOutput
                ),
                'expectedCallbackType' => CallbackInterface::TYPE_COMPILATION_FAILED,
                'expectedCallbackPayload' => [
                    'source' => '/app/source/Test/test.yml',
                    'output' => [
                        'compile-failure-key' => 'value',
                    ],
                ],
            ],
            CompilationCompletedEvent::class => [
                'event' => new CompilationCompletedEvent(),
                'expectedCallbackType' => CallbackInterface::TYPE_COMPILATION_SUCCEEDED,
                'expectedCallbackPayload' => [],
            ],
            ExecutionStartedEvent::class => [
                'event' => new ExecutionStartedEvent(),
                'expectedCallbackType' => CallbackInterface::TYPE_EXECUTION_STARTED,
                'expectedCallbackPayload' => [],
            ],
            JobCompletedEvent::class => [
                'event' => new JobCompletedEvent(),
                'expectedCallbackType' => CallbackInterface::TYPE_JOB_COMPLETED,
                'expectedCallbackPayload' => [],
            ],
            JobTimeoutEvent::class => [
                'event' => new JobTimeoutEvent(10),
                'expectedCallbackType' => CallbackInterface::TYPE_JOB_TIME_OUT,
                'expectedCallbackPayload' => [
                    'maximum_duration_in_seconds' => 10,
                ],
            ],
            TestStartedEvent::class => [
                'event' => new TestStartedEvent(
                    (new MockTest())->getMock(),
                    new Document('document-key: value')
                ),
                'expectedCallbackType' => CallbackInterface::TYPE_TEST_STARTED,
                'expectedCallbackPayload' => [
                    'document-key' => 'value',
                ],
            ],
            TestStepPassedEvent::class => [
                'event' => new TestStepPassedEvent(
                    (new MockTest())->getMock(),
                    new Document('document-key: value')
                ),
                'expectedCallbackType' => CallbackInterface::TYPE_STEP_PASSED,
                'expectedCallbackPayload' => [
                    'document-key' => 'value',
                ],
            ],
            TestStepFailedEvent::class => [
                'event' => new TestStepFailedEvent(
                    (new MockTest())
                        ->withSetStateCall(Test::STATE_FAILED)
                        ->getMock(),
                    new Document('document-key: value')
                ),
                'expectedCallbackType' => CallbackInterface::TYPE_STEP_FAILED,
                'expectedCallbackPayload' => [
                    'document-key' => 'value',
                ],
            ],
        ];
    }
}
