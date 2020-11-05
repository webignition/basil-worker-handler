<?php

declare(strict_types=1);

namespace App\Tests\Mock\Repository;

use App\Entity\Test;
use App\Repository\TestRepository;
use Mockery\MockInterface;

class MockTestRepository
{
    /**
     * @var TestRepository|MockInterface
     */
    private TestRepository $testRepository;

    public function __construct()
    {
        $this->testRepository = \Mockery::mock(TestRepository::class);
    }

    public function getMock(): TestRepository
    {
        return $this->testRepository;
    }

    public function withoutFindCall(): self
    {
        $this->testRepository
            ->shouldNotReceive('find');

        return $this;
    }

    public function withFindCall(int $testId, ?Test $test): self
    {
        $this->testRepository
            ->shouldReceive('find')
            ->with($testId)
            ->andReturn($test);

        return $this;
    }
}