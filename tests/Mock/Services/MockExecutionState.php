<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use Mockery\MockInterface;
use webignition\BasilWorker\StateBundle\Services\ExecutionState;

class MockExecutionState
{
    /**
     * @var ExecutionState|MockInterface
     */
    private ExecutionState $executionState;

    public function __construct()
    {
        $this->executionState = \Mockery::mock(ExecutionState::class);
    }

    public function getMock(): ExecutionState
    {
        return $this->executionState;
    }

    /**
     * @param ExecutionState::STATE_* $state
     *
     * @return $this
     */
    public function withGetCall(string $state): self
    {
        $this->executionState
            ->shouldReceive('get')
            ->andReturn($state);

        return $this;
    }
}
