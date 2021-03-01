<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use App\Event\JobCompletedEvent;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class NoPayloadEventCallbackFactory extends AbstractEventCallbackFactory
{
    /**
     * @var array<class-string, CallbackInterface::TYPE_*>
     */
    private const EVENT_TO_CALLBACK_TYPE_MAP = [
        JobCompletedEvent::class => CallbackInterface::TYPE_JOB_COMPLETED,
    ];

    public function handles(Event $event): bool
    {
        return
            $event instanceof JobCompletedEvent;
    }

    public function createForEvent(Event $event): ?CallbackInterface
    {
        if ($event instanceof JobCompletedEvent) {
            return $this->create(self::EVENT_TO_CALLBACK_TYPE_MAP[$event::class], []);
        }

        return null;
    }
}
