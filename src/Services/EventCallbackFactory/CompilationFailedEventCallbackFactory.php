<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use App\Event\SourceCompilation\SourceCompilationFailedEvent;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class CompilationFailedEventCallbackFactory extends AbstractCompilationEventCallbackFactory
{
    public function handles(Event $event): bool
    {
        return $event instanceof SourceCompilationFailedEvent;
    }

    public function createForEvent(Event $event): ?CallbackInterface
    {
        if ($event instanceof SourceCompilationFailedEvent) {
            return $this->create(
                CallbackInterface::TYPE_COMPILATION_FAILED,
                $this->createPayload($event, [
                    'output' => $event->getOutput()->getData(),
                ])
            );
        }

        return null;
    }
}
