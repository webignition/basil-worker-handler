<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use App\Event\SourceCompilation\SourceCompilationStartedEvent;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class CompilationStartedEventCallbackFactory extends AbstractCompilationEventCallbackFactory
{
    public function handles(Event $event): bool
    {
        return $event instanceof SourceCompilationStartedEvent;
    }

    public function createForEvent(Event $event): ?CallbackInterface
    {
        if ($event instanceof SourceCompilationStartedEvent) {
            return $this->create(
                CallbackInterface::TYPE_COMPILATION_STARTED,
                $this->createPayload($event)
            );
        }

        return null;
    }
}
