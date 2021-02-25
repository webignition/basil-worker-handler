<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\CallbackStateMutator;

class MockCallbackStateMutator
{
    private CallbackStateMutator $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(CallbackStateMutator::class);
    }

    public function getMock(): CallbackStateMutator
    {
        return $this->mock;
    }

    public function withoutSetSendingCall(): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldNotReceive('setSending');

        return $this;
    }

    public function withSetSendingCall(CallbackInterface $callback): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('setSending')
            ->with($callback);

        return $this;
    }
}
