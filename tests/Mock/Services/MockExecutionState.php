<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use Mockery\MockInterface;
use webignition\BasilWorker\StateBundle\Services\ExecutionState;

class MockExecutionState
{
    private ExecutionState $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(ExecutionState::class);
    }

    public function getMock(): ExecutionState
    {
        return $this->mock;
    }

    /**
     * @param ExecutionState::STATE_* $state
     */
    public function withGetCall(string $state): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('get')
            ->andReturn($state);

        return $this;
    }

    /**
     * @param array<ExecutionState::STATE_*> $states
     */
    public function withIsCall(bool $is, ...$states): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('is')
            ->with(...$states)
            ->andReturn($is);

        return $this;
    }
}
