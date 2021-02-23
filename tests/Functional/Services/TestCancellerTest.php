<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\JobTimeoutEvent;
use App\Event\TestFailedEvent;
use App\Services\TestCanceller;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\InvokableFactory\TestGetterFactory;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use Psr\EventDispatcher\EventDispatcherInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class TestCancellerTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private TestCanceller $testCanceller;
    private EventDispatcherInterface $eventDispatcher;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider cancelAwaitingDataProvider
     *
     * @param array<Test::STATE_*> $expectedInitialStates
     * @param array<Test::STATE_*> $expectedStates
     */
    public function testCancelAwaiting(
        InvokableInterface $setup,
        array $expectedInitialStates,
        array $expectedStates
    ): void {
        $this->invokableHandler->invoke($setup);

        self::assertSame($this->invokableHandler->invoke(TestGetterFactory::getStates()), $expectedInitialStates);

        $this->testCanceller->cancelAwaiting();

        self::assertSame($this->invokableHandler->invoke(TestGetterFactory::getStates()), $expectedStates);
    }

    /**
     * @return array[]
     */
    public function cancelAwaitingDataProvider(): array
    {
        return [
            'no tests' => [
                'setup' => Invokable::createEmpty(),
                'expectedInitialStates' => [],
                'expectedStates' => [],
            ],
            'no awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_COMPLETE),
                    (new TestSetup())->withState(Test::STATE_CANCELLED),
                    (new TestSetup())->withState(Test::STATE_FAILED),
                    (new TestSetup())->withState(Test::STATE_RUNNING),
                ]),
                'expectedInitialStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                    Test::STATE_RUNNING,
                ],
                'expectedStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                    Test::STATE_RUNNING,
                ],
            ],
            'all awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                ]),
                'expectedInitialStates' => [
                    Test::STATE_AWAITING,
                    Test::STATE_AWAITING,
                ],
                'expectedStates' => [
                    Test::STATE_CANCELLED,
                    Test::STATE_CANCELLED,
                ],
            ],
            'mixed' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_COMPLETE),
                    (new TestSetup())->withState(Test::STATE_CANCELLED),
                    (new TestSetup())->withState(Test::STATE_FAILED),
                    (new TestSetup())->withState(Test::STATE_RUNNING),
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                ]),
                'expectedInitialStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                    Test::STATE_RUNNING,
                    Test::STATE_AWAITING,
                ],
                'expectedStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                    Test::STATE_RUNNING,
                    Test::STATE_CANCELLED,
                ],
            ],
        ];
    }

    /**
     * @dataProvider cancelUnfinishedDataProvider
     *
     * @param array<Test::STATE_*> $expectedInitialStates
     * @param array<Test::STATE_*> $expectedStates
     */
    public function testCancelUnfinished(
        InvokableInterface $setup,
        array $expectedInitialStates,
        array $expectedStates
    ): void {
        $this->invokableHandler->invoke($setup);

        self::assertSame($this->invokableHandler->invoke(TestGetterFactory::getStates()), $expectedInitialStates);

        $this->testCanceller->cancelUnfinished();

        self::assertSame($this->invokableHandler->invoke(TestGetterFactory::getStates()), $expectedStates);
    }

    /**
     * @return array[]
     */
    public function cancelUnfinishedDataProvider(): array
    {
        return [
            'no tests' => [
                'setup' => Invokable::createEmpty(),
                'expectedInitialStates' => [],
                'expectedStates' => [],
            ],
            'no unfinished tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_COMPLETE),
                    (new TestSetup())->withState(Test::STATE_CANCELLED),
                    (new TestSetup())->withState(Test::STATE_FAILED),
                ]),
                'expectedInitialStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                ],
                'expectedStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                ],
            ],
            'all unfinished tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                    (new TestSetup())->withState(Test::STATE_RUNNING),
                ]),
                'expectedInitialStates' => [
                    Test::STATE_AWAITING,
                    Test::STATE_RUNNING,
                ],
                'expectedStates' => [
                    Test::STATE_CANCELLED,
                    Test::STATE_CANCELLED,
                ],
            ],
            'mixed' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_COMPLETE),
                    (new TestSetup())->withState(Test::STATE_CANCELLED),
                    (new TestSetup())->withState(Test::STATE_FAILED),
                    (new TestSetup())->withState(Test::STATE_RUNNING),
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                ]),
                'expectedInitialStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                    Test::STATE_RUNNING,
                    Test::STATE_AWAITING,
                ],
                'expectedStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                    Test::STATE_CANCELLED,
                    Test::STATE_CANCELLED,
                ],
            ],
        ];
    }

    /**
     * @dataProvider cancelAwaitingFromTestFailedEventDataProvider
     *
     * @param array<Test::STATE_*> $expectedInitialStates
     * @param array<Test::STATE_*> $expectedStates
     */
    public function testCancelAwaitingFromTestFailedEvent(
        InvokableInterface $setup,
        array $expectedInitialStates,
        array $expectedStates
    ): void {
        $this->doTestFailedEventDrivenTest(
            $setup,
            $expectedInitialStates,
            function (TestFailedEvent $event) {
                $this->testCanceller->cancelAwaitingFromTestFailedEvent($event);
            },
            $expectedStates
        );
    }

    /**
     * @dataProvider cancelAwaitingFromTestFailedEventDataProvider
     *
     * @param array<Test::STATE_*> $expectedInitialStates
     * @param array<Test::STATE_*> $expectedStates
     */
    public function testSubscribesToTestExecuteDocumentReceivedEvent(
        InvokableInterface $setup,
        array $expectedInitialStates,
        array $expectedStates
    ): void {
        $this->doTestFailedEventDrivenTest(
            $setup,
            $expectedInitialStates,
            function (TestFailedEvent $event) {
                $this->eventDispatcher->dispatch($event);
            },
            $expectedStates
        );
    }

    /**
     * @return array[]
     */
    public function cancelAwaitingFromTestFailedEventDataProvider(): array
    {
        return [
            'no awaiting tests, test not failed' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_RUNNING),
                    (new TestSetup())->withState(Test::STATE_COMPLETE),
                ]),
                'expectedInitialStates' => [
                    Test::STATE_RUNNING,
                    Test::STATE_COMPLETE,
                ],
                'expectedStates' => [
                    Test::STATE_RUNNING,
                    Test::STATE_COMPLETE,
                ],
            ],
            'has awaiting tests, test not failed' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_RUNNING),
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                ]),
                'expectedInitialStates' => [
                    Test::STATE_RUNNING,
                    Test::STATE_AWAITING,
                    Test::STATE_AWAITING,
                ],
                'expectedStates' => [
                    Test::STATE_RUNNING,
                    Test::STATE_AWAITING,
                    Test::STATE_AWAITING,
                ],
            ],
            'no awaiting tests, test failed' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_FAILED),
                    (new TestSetup())->withState(Test::STATE_COMPLETE),
                ]),
                'expectedInitialStates' => [
                    Test::STATE_FAILED,
                    Test::STATE_COMPLETE,
                ],
                'expectedStates' => [
                    Test::STATE_FAILED,
                    Test::STATE_COMPLETE,
                ],
            ],
            'has awaiting tests, test failed' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_FAILED),
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                ]),
                'expectedInitialStates' => [
                    Test::STATE_FAILED,
                    Test::STATE_AWAITING,
                    Test::STATE_AWAITING,
                ],
                'expectedStates' => [
                    Test::STATE_FAILED,
                    Test::STATE_CANCELLED,
                    Test::STATE_CANCELLED,
                ],
            ],
        ];
    }

    /**
     * @dataProvider subscribesToJobTimeoutEventDataProvider
     *
     * @param array<Test::STATE_*> $expectedInitialStates
     * @param array<Test::STATE_*> $expectedStates
     */
    public function testSubscribesToJobTimeoutEvent(
        InvokableInterface $setup,
        array $expectedInitialStates,
        array $expectedStates
    ): void {
        $tests = $this->invokableHandler->invoke($setup);
        self::assertSame($this->invokableHandler->invoke(TestGetterFactory::getStates()), $expectedInitialStates);

        $test = $tests[0];
        self::assertInstanceOf(Test::class, $test);

        $event = new JobTimeoutEvent(10);
        $this->eventDispatcher->dispatch($event);

        self::assertSame($this->invokableHandler->invoke(TestGetterFactory::getStates()), $expectedStates);
    }

    /**
     * @return array[]
     */
    public function subscribesToJobTimeoutEventDataProvider(): array
    {
        return [
            'no unfinished tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_COMPLETE),
                    (new TestSetup())->withState(Test::STATE_CANCELLED),
                    (new TestSetup())->withState(Test::STATE_FAILED),
                ]),
                'expectedInitialStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                ],
                'expectedStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                ],
            ],
            'has unfinished tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                    (new TestSetup())->withState(Test::STATE_RUNNING),
                ]),
                'expectedInitialStates' => [
                    Test::STATE_AWAITING,
                    Test::STATE_RUNNING,
                ],
                'expectedStates' => [
                    Test::STATE_CANCELLED,
                    Test::STATE_CANCELLED,
                ],
            ],
            'mixed' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())->withState(Test::STATE_COMPLETE),
                    (new TestSetup())->withState(Test::STATE_CANCELLED),
                    (new TestSetup())->withState(Test::STATE_FAILED),
                    (new TestSetup())->withState(Test::STATE_AWAITING),
                    (new TestSetup())->withState(Test::STATE_RUNNING),
                ]),
                'expectedInitialStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                    Test::STATE_AWAITING,
                    Test::STATE_RUNNING,
                ],
                'expectedStates' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_CANCELLED,
                    Test::STATE_FAILED,
                    Test::STATE_CANCELLED,
                    Test::STATE_CANCELLED,
                ],
            ],
        ];
    }

    /**
     * @param InvokableInterface $setup
     * @param array<Test::STATE_*> $expectedInitialStates
     * @param callable $execute
     * @param array<Test::STATE_*> $expectedStates
     */
    private function doTestFailedEventDrivenTest(
        InvokableInterface $setup,
        array $expectedInitialStates,
        callable $execute,
        array $expectedStates
    ): void {
        $tests = $this->invokableHandler->invoke($setup);
        self::assertSame($this->invokableHandler->invoke(TestGetterFactory::getStates()), $expectedInitialStates);

        $test = $tests[0];
        self::assertInstanceOf(Test::class, $test);

        $event = new TestFailedEvent($test);
        $execute($event);

        self::assertSame($this->invokableHandler->invoke(TestGetterFactory::getStates()), $expectedStates);
    }
}
