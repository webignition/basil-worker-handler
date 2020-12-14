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
    private const MILLISECONDS_PER_SECOND = 1000;

    private MessageBusInterface $messageBus;
    private int $recheckPeriodInSeconds;
    private bool $enabled;

    public function __construct(
        MessageBusInterface $messageBus,
        int $recheckPeriodInSeconds,
        bool $enabled = true
    ) {
        $this->messageBus = $messageBus;
        $this->recheckPeriodInSeconds = $recheckPeriodInSeconds;
        $this->enabled = $enabled;
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
        $message = new TimeoutCheckMessage();
        $envelope = new Envelope($message, [
            new DelayStamp($this->recheckPeriodInSeconds * self::MILLISECONDS_PER_SECOND)
        ]);

        if ($this->enabled) {
            $this->messageBus->dispatch($envelope);
        }
    }
}
