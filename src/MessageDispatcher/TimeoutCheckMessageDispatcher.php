<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Event\JobReadyEvent;
use App\Message\TimeoutCheckMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class TimeoutCheckMessageDispatcher implements EventSubscriberInterface
{
    public const MILLISECONDS_PER_SECOND = 1000;

    public function __construct(
        private MessageBusInterface $messageBus,
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
        $this->messageBus->dispatch(new TimeoutCheckMessage());
    }
}
