<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\JobCompletedEvent;
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

        if ($event instanceof JobCompletedEvent) {
            return $this->persistenceBundleCallbackFactory->create(CallbackInterface::TYPE_JOB_COMPLETED, []);
        }

        return null;
    }
}
