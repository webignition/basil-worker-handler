<?php

declare(strict_types=1);

namespace App\Tests\Mock\Entity;

use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class MockCallback
{
    private CallbackInterface $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(CallbackInterface::class);
    }

    public function getMock(): CallbackInterface
    {
        return $this->mock;
    }

    /**
     * @param CallbackInterface::TYPE_* $type
     */
    public function withGetTypeCall(string $type): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('getType')
            ->andReturn($type);

        return $this;
    }

    /**
     * @param array<mixed> $payload
     */
    public function withGetPayloadCall(array $payload): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('getPayload')
            ->andReturn($payload);

        return $this;
    }
}
