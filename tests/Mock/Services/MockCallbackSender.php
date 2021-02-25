<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\CallbackSender;
use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class MockCallbackSender
{
    private CallbackSender $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(CallbackSender::class);
    }

    public function getMock(): CallbackSender
    {
        return $this->mock;
    }

    public function withSendCall(CallbackInterface $callback): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('send')
            ->with($callback);

        return $this;
    }

    public function withoutSendCall(): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldNotReceive('send');

        return $this;
    }
}
