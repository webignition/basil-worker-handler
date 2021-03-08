<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Event\JobReadyEvent;
use App\Message\TimeoutCheckMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TimeoutCheckMessageDispatcher extends AbstractDelayedMessageDispatcher implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            JobReadyEvent::class => [
                ['dispatch', 0],
            ],
        ];
    }

    protected function createMessage(): object
    {
        return new TimeoutCheckMessage();
    }
}
