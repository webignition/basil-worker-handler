<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Event\JobReadyEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SourcesAddedEventSubscriber implements EventSubscriberInterface
{
    private ?JobReadyEvent $event = null;

    public static function getSubscribedEvents()
    {
        return [
            JobReadyEvent::class => 'onSourcesAdded',
        ];
    }

    public function onSourcesAdded(JobReadyEvent $event): void
    {
        $this->event = $event;
    }

    public function getEvent(): ?JobReadyEvent
    {
        return $this->event;
    }
}
