<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use App\Event\SourceCompile\CompilationFailedEvent;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\CallbackFactory as PersistenceBundleCallbackFactory;

class CompilationFailedEventCallbackFactory implements EventCallbackFactoryInterface
{
    public function __construct(
        private PersistenceBundleCallbackFactory $persistenceBundleCallbackFactory
    ) {
    }

    public function handles(Event $event): bool
    {
        return $event instanceof CompilationFailedEvent;
    }

    public function create(Event $event): ?CallbackInterface
    {
        if ($event instanceof CompilationFailedEvent) {
            return $this->persistenceBundleCallbackFactory->create(
                CallbackInterface::TYPE_COMPILATION_FAILED,
                $event->getOutput()->getData()
            );
        }

        return null;
    }
}
