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

    public function withGetStateCall(string $state): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('getState')
            ->andReturn($state);

        return $this;
    }
}
