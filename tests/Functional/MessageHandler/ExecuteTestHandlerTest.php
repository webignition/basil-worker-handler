<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Message\ExecuteTestMessage;
use App\MessageHandler\ExecuteTestHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockTestExecutor;
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

    public function testInvokeExecuteSuccess()
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

        $executeTestMessage = new ExecuteTestMessage((int) $test->getId());

        ObjectReflector::setProperty($this->handler, ExecuteTestHandler::class, 'testExecutor', $testExecutor);

        $handler = $this->handler;
        $handler($executeTestMessage);

        self::assertTrue($job->hasStarted());

        $executionState = $this->invokableHandler->invoke(ExecutionStateGetterFactory::get());
        self::assertSame(ExecutionState::STATE_COMPLETE, $executionState);
        self::assertSame(Test::STATE_COMPLETE, $test->getState());
    }
}
