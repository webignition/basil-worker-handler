<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use App\Event\SourceCompilation\SourceCompilationPassedEvent;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class CompilationPassedEventCallbackFactory extends AbstractCompilationEventCallbackFactory
{
    public function handles(Event $event): bool
    {
        return $event instanceof SourceCompilationPassedEvent;
    }

    public function createForEvent(Event $event): ?CallbackInterface
    {
        if ($event instanceof SourceCompilationPassedEvent) {
            return $this->create(
                CallbackInterface::TYPE_COMPILATION_PASSED,
                $this->createPayload($event)
            );
        }

        return null;
    }
}
