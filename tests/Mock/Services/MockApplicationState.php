<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use Mockery\MockInterface;
use webignition\BasilWorker\StateBundle\Services\ApplicationState;

class MockApplicationState
{
    private ApplicationState $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(ApplicationState::class);
    }

    public function getMock(): ApplicationState
    {
        return $this->mock;
    }

    /**
     * @param array<ApplicationState::STATE_*> $states
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
