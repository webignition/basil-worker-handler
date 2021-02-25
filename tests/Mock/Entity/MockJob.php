<?php

declare(strict_types=1);

namespace App\Tests\Mock\Entity;

use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;

class MockJob
{
    private Job $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(Job::class);
    }

    public function getMock(): Job
    {
        return $this->mock;
    }

    public function withGetCallbackUrlCall(string $callbackUrl): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('getCallbackUrl')
            ->andReturn($callbackUrl);

        return $this;
    }

    public function withGetLabelCall(string $label): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('getLabel')
            ->andReturn($label);

        return $this;
    }

    public function withHasReachedMaximumDurationCall(bool $hasReachedMaximumDuration): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('hasReachedMaximumDuration')
            ->andReturn($hasReachedMaximumDuration);

        return $this;
    }

    public function withGetMaximumDurationInSecondsCall(int $maximumDurationInSeconds): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('getMaximumDurationInSeconds')
            ->andReturn($maximumDurationInSeconds);

        return $this;
    }
}
