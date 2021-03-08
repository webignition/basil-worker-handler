<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

abstract class AbstractDelayedMessageDispatcher
{
    public const MILLISECONDS_PER_SECOND = 1000;

    public function __construct(
        private MessageBusInterface $messageBus,
        private int $recheckPeriodInSeconds,
        private bool $enabled = true
    ) {
    }

    abstract protected function createMessage(): object;

    public function dispatch(): void
    {
        if ($this->enabled) {
            $message = $this->createMessage();
            $envelope = new Envelope($message, [
                new DelayStamp($this->recheckPeriodInSeconds * self::MILLISECONDS_PER_SECOND)
            ]);


            $this->messageBus->dispatch($envelope);
        }
    }
}
