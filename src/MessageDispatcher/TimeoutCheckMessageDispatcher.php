<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Event\JobReadyEvent;
use App\Message\TimeoutCheckMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class TimeoutCheckMessageDispatcher implements EventSubscriberInterface
{
    public const MILLISECONDS_PER_SECOND = 1000;

    public function __construct(
        private MessageBusInterface $messageBus,
        private int $recheckPeriodInSeconds,
        private bool $enabled = true
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            JobReadyEvent::class => [
                ['dispatch', 0],
            ],
        ];
    }

    public function dispatch(): void
    {
        if ($this->enabled) {
            $message = new TimeoutCheckMessage();
            $envelope = new Envelope($message, [
                new DelayStamp($this->recheckPeriodInSeconds * self::MILLISECONDS_PER_SECOND)
            ]);


            $this->messageBus->dispatch($envelope);
        }
    }
}
