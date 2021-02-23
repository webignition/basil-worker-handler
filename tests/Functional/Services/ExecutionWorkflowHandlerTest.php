<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Event\TestExecuteCompleteEvent;
use App\Message\ExecuteTestMessage;
use App\Services\ExecutionWorkflowHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockSuiteManifest;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use Psr\EventDispatcher\EventDispatcherInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class ExecutionWorkflowHandlerTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private ExecutionWorkflowHandler $handler;
    private EventDispatcherInterface $eventDispatcher;
    private MessengerAsserter $messengerAsserter;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $this->invokableHandler->invoke(JobSetupInvokableFactory::setup());
    }

    public function testDispatchNextExecuteTestMessageNoMessageDispatched(): void
    {
        $this->handler->dispatchNextExecuteTestMessage();
        $this->messengerAsserter->assertQueueIsEmpty();
    }

    /**
     * @dataProvider dispatchNextExecuteTestMessageMessageDispatchedDataProvider
     */
    public function testDispatchNextExecuteTestMessageMessageDispatched(
        InvokableInterface $setup,
        int $expectedNextTestIndex
    ): void {
        $this->doSourceCompileSuccessEventDrivenTest(
            $setup,
            function () {
                $this->handler->dispatchNextExecuteTestMessage();
            },
            $expectedNextTestIndex,
        );
    }

    /**
     * @return array[]
     */
    public function dispatchNextExecuteTestMessageMessageDispatchedDataProvider(): array
    {
        return [
            'two tests, none run' => [
                'setup' => new InvokableCollection([
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml'),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test2.yml'),
                    ]),
                ]),
                'expectedNextTestIndex' => 0,
            ],
            'three tests, first complete' => [
                'setup' => new InvokableCollection([
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test2.yml'),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test3.yml'),
                    ]),
                ]),
                'expectedNextTestIndex' => 1,
            ],
            'three tests, first, second complete' => [
                'setup' => new InvokableCollection([
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test2.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test3.yml'),
                    ]),
                ]),
                'expectedNextTestIndex' => 2,
            ],
        ];
    }

    public function testSubscribesToSourceCompileSuccessEvent(): void
    {
        $this->doSourceCompileSuccessEventDrivenTest(
            new InvokableCollection([
                TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/Test/test1.yml')
                        ->withState(Test::STATE_COMPLETE),
                    (new TestSetup())
                        ->withSource('/app/source/Test/test2.yml'),
                ]),
            ]),
            function () {
                $this->eventDispatcher->dispatch(
                    new SourceCompileSuccessEvent(
                        '/app/source/Test/test1.yml',
                        (new MockSuiteManifest())
                            ->withGetTestManifestsCall([])
                            ->getMock()
                    ),
                );
            },
            1,
        );
    }

    private function doSourceCompileSuccessEventDrivenTest(
        InvokableInterface $setup,
        callable $execute,
        int $expectedNextTestIndex
    ): void {
        $this->messengerAsserter->assertQueueIsEmpty();

        $tests = $this->invokableHandler->invoke($setup);
        $execute();

        $this->messengerAsserter->assertQueueCount(1);

        $expectedNextTest = $tests[$expectedNextTestIndex] ?? null;
        self::assertInstanceOf(Test::class, $expectedNextTest);

        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            new ExecuteTestMessage((int) $expectedNextTest->getId())
        );
    }

    /**
     * @dataProvider dispatchNextExecuteTestMessageFromTestExecuteCompleteEventDataProvider
     */
    public function testDispatchNextExecuteTestMessageFromTestExecuteCompleteEvent(
        InvokableInterface $setup,
        int $eventTestIndex,
        int $expectedQueuedMessageCount,
        ?int $expectedNextTestIndex
    ): void {
        $this->doTestExecuteCompleteEventDrivenTest(
            $setup,
            $eventTestIndex,
            function (TestExecuteCompleteEvent $event) {
                $this->handler->dispatchNextExecuteTestMessageFromTestExecuteCompleteEvent($event);
            },
            $expectedQueuedMessageCount,
            $expectedNextTestIndex
        );
    }

    /**
     * @return array[]
     */
    public function dispatchNextExecuteTestMessageFromTestExecuteCompleteEventDataProvider(): array
    {
        return [
            'single test, not complete' => [
                'setup' => new InvokableCollection([
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_FAILED),
                    ])
                ]),
                'eventTestIndex' => 0,
                'expectedQueuedMessageCount' => 0,
                'expectedNextTestIndex' => null,
            ],
            'single test, is complete' => [
                'setup' => new InvokableCollection([
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                    ])
                ]),
                'eventTestIndex' => 0,
                'expectedQueuedMessageCount' => 0,
                'expectedNextTestIndex' => null,
            ],
            'multiple tests, not complete' => [
                'setup' => new InvokableCollection([
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_FAILED),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test2.yml')
                            ->withState(Test::STATE_AWAITING),
                    ])
                ]),
                'eventTestIndex' => 0,
                'expectedQueuedMessageCount' => 0,
                'expectedNextTestIndex' => null,
            ],
            'multiple tests, complete' => [
                'setup' => new InvokableCollection([
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withState(Test::STATE_COMPLETE),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test2.yml')
                            ->withState(Test::STATE_AWAITING),
                    ])
                ]),
                'eventTestIndex' => 0,
                'expectedQueuedMessageCount' => 1,
                'expectedNextTestIndex' => 1,
            ],
        ];
    }

    public function testSubscribesToTestExecuteCompleteEvent(): void
    {
        $this->doTestExecuteCompleteEventDrivenTest(
            new InvokableCollection([
                TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/Test/test1.yml')
                        ->withState(Test::STATE_COMPLETE),
                    (new TestSetup())
                        ->withSource('/app/source/Test/test2.yml')
                        ->withState(Test::STATE_AWAITING),
                ])
            ]),
            0,
            function (TestExecuteCompleteEvent $event) {
                $this->eventDispatcher->dispatch($event);
            },
            1,
            1
        );
    }

    private function doTestExecuteCompleteEventDrivenTest(
        InvokableInterface $setup,
        int $eventTestIndex,
        callable $execute,
        int $expectedQueuedMessageCount,
        ?int $expectedNextTestIndex
    ): void {
        $tests = $this->invokableHandler->invoke($setup);
        $this->messengerAsserter->assertQueueIsEmpty();

        $test = $tests[$eventTestIndex];
        $event = new TestExecuteCompleteEvent($test);

        $execute($event);

        $this->messengerAsserter->assertQueueCount($expectedQueuedMessageCount);

        if (is_int($expectedNextTestIndex)) {
            $expectedNextTest = $tests[$expectedNextTestIndex] ?? null;
            self::assertInstanceOf(Test::class, $expectedNextTest);

            $this->messengerAsserter->assertMessageAtPositionEquals(
                0,
                new ExecuteTestMessage((int) $expectedNextTest->getId())
            );
        }
    }
}
