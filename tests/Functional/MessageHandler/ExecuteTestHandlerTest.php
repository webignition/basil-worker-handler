<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Event\TestExecuteCompleteEvent;
use App\Event\TestStartedEvent;
use App\Message\ExecuteTestMessage;
use App\MessageHandler\ExecuteTestHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockEventDispatcher;
use App\Tests\Mock\Services\MockTestExecutor;
use App\Tests\Model\ExpectedDispatchedEvent;
use App\Tests\Model\ExpectedDispatchedEventCollection;
use App\Tests\Services\InvokableFactory\ExecutionStateGetterFactory;
use App\Tests\Services\InvokableFactory\JobGetterFactory;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\StateBundle\Services\ExecutionState;
use webignition\ObjectReflector\ObjectReflector;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class ExecuteTestHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private ExecuteTestHandler $handler;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testInvokeExecuteSuccess(): void
    {
        $this->invokableHandler->invoke(JobSetupInvokableFactory::setup());

        $tests = $this->invokableHandler->invoke(TestSetupInvokableFactory::setupCollection([
            new TestSetup(),
        ]));

        $test = $tests[0];

        $job = $this->invokableHandler->invoke(JobGetterFactory::get());
        self::assertInstanceOf(Job::class, $job);
        self::assertFalse($job->hasStarted());

        $executionState = $this->invokableHandler->invoke(ExecutionStateGetterFactory::get());
        self::assertSame(ExecutionState::STATE_AWAITING, $executionState);
        self::assertSame(Test::STATE_AWAITING, $test->getState());

        $testExecutor = (new MockTestExecutor())
            ->withExecuteCall($test)
            ->getMock();

        ObjectReflector::setProperty($this->handler, ExecuteTestHandler::class, 'testExecutor', $testExecutor);

        $eventDispatcher = (new MockEventDispatcher())
            ->withDispatchCalls(new ExpectedDispatchedEventCollection([
                new ExpectedDispatchedEvent(
                    function (TestStartedEvent $actualEvent) use ($test) {
                        self::assertSame($test, $actualEvent->getTest());

                        return true;
                    },
                ),
                new ExpectedDispatchedEvent(
                    function (TestExecuteCompleteEvent $actualEvent) use ($test) {
                        self::assertSame($test, $actualEvent->getTest());

                        return true;
                    },
                ),
            ]))
            ->getMock();

        ObjectReflector::setProperty($this->handler, ExecuteTestHandler::class, 'eventDispatcher', $eventDispatcher);

        $handler = $this->handler;

        $executeTestMessage = new ExecuteTestMessage((int) $test->getId());
        $handler($executeTestMessage);

        self::assertTrue($job->hasStarted());

        $executionState = $this->invokableHandler->invoke(ExecutionStateGetterFactory::get());
        self::assertSame(ExecutionState::STATE_COMPLETE, $executionState);
        self::assertSame(Test::STATE_COMPLETE, $test->getState());
    }
}
