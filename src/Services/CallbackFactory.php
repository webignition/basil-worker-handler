<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\JobCompleteEvent;
use App\Event\JobTimeoutEvent;
use App\Services\EventCallbackFactory\EventCallbackFactoryInterface;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\CallbackFactory as PersistenceBundleCallbackFactory;

class CallbackFactory
{
    /**
     * @var EventCallbackFactoryInterface[]
     */
    private array $eventCallbackFactories;

    /**
     * @param array<mixed> $eventCallbackFactories
     */
    public function __construct(
        private PersistenceBundleCallbackFactory $persistenceBundleCallbackFactory,
        array $eventCallbackFactories
    ) {
        $this->eventCallbackFactories = array_filter($eventCallbackFactories, function ($item) {
            return $item instanceof EventCallbackFactoryInterface;
        });
    }

    public function createForEvent(Event $event): ?CallbackInterface
    {
        foreach ($this->eventCallbackFactories as $eventCallbackFactory) {
            if ($eventCallbackFactory->handles($event)) {
                return $eventCallbackFactory->create($event);
            }
        }

        if ($event instanceof JobTimeoutEvent) {
            return $this->persistenceBundleCallbackFactory->create(
                CallbackInterface::TYPE_JOB_TIME_OUT,
                [
                    'maximum_duration_in_seconds' => $event->getJobMaximumDuration(),
                ]
            );
        }

        if ($event instanceof JobCompleteEvent) {
            return $this->persistenceBundleCallbackFactory->create(CallbackInterface::TYPE_JOB_COMPLETED, []);
        }

        return null;
    }
}
