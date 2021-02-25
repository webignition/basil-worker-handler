<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Tests\Model\ExpectedDispatchedEvent;
use App\Tests\Model\ExpectedDispatchedEventCollection;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

class MockEventDispatcher
{
    private EventDispatcherInterface $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(EventDispatcherInterface::class);
    }

    public function getMock(): EventDispatcherInterface
    {
        return $this->mock;
    }

    public function withDispatchCalls(ExpectedDispatchedEventCollection $expectedDispatchedEvents): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('dispatch')
            ->withArgs(function (Event $passedEvent) use ($expectedDispatchedEvents) {
                static $dispatchCallIndex = 0;

                $expectedDispatchedEvent = $expectedDispatchedEvents[$dispatchCallIndex];

                if ($expectedDispatchedEvent instanceof ExpectedDispatchedEvent) {
                    TestCase::assertTrue($expectedDispatchedEvent->matches($passedEvent));
                }

                $dispatchCallIndex++;

                return $expectedDispatchedEvent instanceof ExpectedDispatchedEvent;
            });

        return $this;
    }

    public function withoutDispatchCall(): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldNotReceive('dispatch');

        return $this;
    }
}
