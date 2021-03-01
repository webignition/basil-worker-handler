<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use Symfony\Component\Messenger\MessageBusInterface;

class MockMessageBus
{
    private MessageBusInterface $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(MessageBusInterface::class);
    }

    public function getMock(): MessageBusInterface
    {
        return $this->mock;
    }
}
