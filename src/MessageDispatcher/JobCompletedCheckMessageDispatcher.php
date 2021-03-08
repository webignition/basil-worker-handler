<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Message\JobCompletedCheckMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class JobCompletedCheckMessageDispatcher
{
    public const MILLISECONDS_PER_SECOND = 1000;

    public function __construct(
        private MessageBusInterface $messageBus,
        private int $recheckPeriodInSeconds,
        private bool $enabled = true
    ) {
    }

    public function dispatch(): void
    {
        $message = new JobCompletedCheckMessage();
        $envelope = new Envelope($message, [
            new DelayStamp($this->recheckPeriodInSeconds * self::MILLISECONDS_PER_SECOND)
        ]);

        if ($this->enabled) {
            $this->messageBus->dispatch($envelope);
        }
    }
}
