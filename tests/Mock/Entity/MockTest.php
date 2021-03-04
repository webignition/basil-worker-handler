<?php

declare(strict_types=1);

namespace App\Tests\Mock\Entity;

use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;

class MockTest
{
    private Test $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(Test::class);
    }

    public function getMock(): Test
    {
        return $this->mock;
    }

    /**
     * @param Test::STATE_* $state
     */
    public function withHasStateCall(string $state, bool $has): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('hasState')
            ->with($state)
            ->andReturn($has);

        return $this;
    }

    public function withSetStateCall(string $state): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('setState')
            ->with($state);

        return $this;
    }
}
