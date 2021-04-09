<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Event\JobReadyEvent;
use App\Message\TimeoutCheckMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use webignition\SymfonyMessengerMessageDispatcher\MessageDispatcher;

class TimeoutCheckMessageDispatcher implements EventSubscriberInterface
{
    public const MILLISECONDS_PER_SECOND = 1000;

    public function __construct(
        private MessageDispatcher $messageDispatcher,
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
        $this->messageDispatcher->dispatch(new TimeoutCheckMessage());
    }
}
