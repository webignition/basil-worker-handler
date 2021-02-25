<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;

class MockJobStore
{
    private JobStore $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(JobStore::class);
    }

    public function getMock(): JobStore
    {
        return $this->mock;
    }

    public function withHasCall(bool $return): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('has')
            ->andReturn($return);

        return $this;
    }

    public function withGetCall(Job $job): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('get')
            ->andReturn($job);

        return $this;
    }
}
