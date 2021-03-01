<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use App\Event\JobTimeoutEvent;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class JobTimeoutEventCallbackFactory extends AbstractEventCallbackFactory
{
    public function handles(Event $event): bool
    {
        return $event instanceof JobTimeoutEvent;
    }

    public function createForEvent(Event $event): ?CallbackInterface
    {
        if ($event instanceof JobTimeoutEvent) {
            return $this->create(CallbackInterface::TYPE_JOB_TIME_OUT, [
                'maximum_duration_in_seconds' => $event->getJobMaximumDuration(),
            ]);
        }

        return null;
    }
}
