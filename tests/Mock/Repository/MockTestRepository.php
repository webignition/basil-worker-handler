<?php

declare(strict_types=1);

namespace App\Tests\Mock\Repository;

use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\TestRepository;

class MockTestRepository
{
    private TestRepository $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(TestRepository::class);
    }

    public function getMock(): TestRepository
    {
        return $this->mock;
    }

    public function withoutFindCall(): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldNotReceive('find');

        return $this;
    }

    public function withFindCall(int $testId, ?Test $test): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('find')
            ->with($testId)
            ->andReturn($test);

        return $this;
    }
}
