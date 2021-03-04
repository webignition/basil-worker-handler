<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Message\ExecuteTestMessage;
use App\MessageHandler\ExecuteTestHandler;
use App\Services\TestStateMutator;
use App\Tests\Mock\Entity\MockJob;
use App\Tests\Mock\Entity\MockTest;
use App\Tests\Mock\Repository\MockTestRepository;
use App\Tests\Mock\Services\MockExecutionState;
use App\Tests\Mock\Services\MockJobStore;
use App\Tests\Mock\Services\MockTestExecutor;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\EntityPersister;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\TestRepository;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;
use webignition\BasilWorker\StateBundle\Services\ExecutionState;

class ExecuteTestHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider invokeNoExecutionDataProvider
     */
    public function testInvokeNoExecution(
        JobStore $jobStore,
        ExecutionState $executionState,
        ExecuteTestMessage $message,
        TestRepository $testRepository
    ): void {
        $testExecutor = (new MockTestExecutor())
            ->withoutExecuteCall()
            ->getMock();

        $handler = new ExecuteTestHandler(
            $jobStore,
            \Mockery::mock(EntityPersister::class),
            $testExecutor,
            \Mockery::mock(EventDispatcherInterface::class),
            \Mockery::mock(TestStateMutator::class),
            $testRepository,
            $executionState
        );

        $handler($message);
    }

    /**
     * @return array[]
     */
    public function invokeNoExecutionDataProvider(): array
    {
        $testInWrongState = (new MockTest())
            ->withGetStateCall(Test::STATE_FAILED)
            ->getMock();

        return [
            'no job' => [
                'jobStore' => (new MockJobStore())
                    ->withHasCall(false)
                    ->getMock(),
                'executionState' => (new MockExecutionState())
                    ->getMock(),
                'message' => new ExecuteTestMessage(1),
                'testRepository' => (new MockTestRepository())
                    ->withoutFindCall()
                    ->getMock(),
            ],
            'execution state not awaiting, not running' => [
                'jobStore' => (new MockJobStore())
                    ->withHasCall(true)
                    ->withGetCall((new MockJob())->getMock())
                    ->getMock(),
                'executionState' => (new MockExecutionState())
                    ->withIsCall(true, ...ExecutionState::FINISHED_STATES)
                    ->getMock(),
                'message' => new ExecuteTestMessage(1),
                'testRepository' => (new MockTestRepository())
                    ->withoutFindCall()
                    ->getMock(),
            ],
            'no test' => [
                'jobStore' => (new MockJobStore())
                    ->withHasCall(true)
                    ->withGetCall((new MockJob())->getMock())
                    ->getMock(),
                'executionState' => (new MockExecutionState())
                    ->withIsCall(false, ...ExecutionState::FINISHED_STATES)
                    ->getMock(),
                'message' => new ExecuteTestMessage(1),
                'testRepository' => (new MockTestRepository())
                    ->withFindCall(1, null)
                    ->getMock(),
            ],
            'test in wrong state' => [
                'jobStore' => (new MockJobStore())
                    ->withHasCall(true)
                    ->withGetCall((new MockJob())->getMock())
                    ->getMock(),
                'executionState' => (new MockExecutionState())
                    ->withIsCall(false, ...ExecutionState::FINISHED_STATES)
                    ->getMock(),
                'message' => new ExecuteTestMessage(1),
                'testRepository' => (new MockTestRepository())
                    ->withFindCall(1, $testInWrongState)
                    ->getMock(),
            ],
        ];
    }
}
