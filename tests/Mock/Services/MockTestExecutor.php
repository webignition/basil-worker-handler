<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\TestExecutor;
use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;

class MockTestExecutor
{
    private TestExecutor $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(TestExecutor::class);
    }

    public function getMock(): TestExecutor
    {
        return $this->mock;
    }

    public function withoutExecuteCall(): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldNotReceive('execute');

        return $this;
    }

    public function withExecuteCall(Test $test): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('execute')
            ->with($test);

        return $this;
    }
}
