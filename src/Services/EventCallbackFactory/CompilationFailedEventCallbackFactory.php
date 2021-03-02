<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use App\Event\SourceCompilation\SourceCompilationFailedEvent;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class CompilationFailedEventCallbackFactory extends AbstractEventCallbackFactory
{
    public function handles(Event $event): bool
    {
        return $event instanceof SourceCompilationFailedEvent;
    }

    public function createForEvent(Event $event): ?CallbackInterface
    {
        if ($event instanceof SourceCompilationFailedEvent) {
            return $this->create(CallbackInterface::TYPE_COMPILATION_FAILED, $event->getOutput()->getData());
        }

        return null;
    }
}
