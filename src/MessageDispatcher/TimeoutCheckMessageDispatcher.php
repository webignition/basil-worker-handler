<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Event\JobReadyEvent;
use App\Message\TimeoutCheckMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;

class TimeoutCheckMessageDispatcher extends AbstractMessageDispatcher implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            JobReadyEvent::class => [
                ['dispatch', 0],
            ],
        ];
    }

    public function dispatch(): Envelope
    {
        return $this->doDispatch(new TimeoutCheckMessage());
    }
}
