<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use App\Event\CompilationCompletedEvent;
use App\Event\ExecutionCompletedEvent;
use App\Event\ExecutionStartedEvent;
use App\Event\JobCompletedEvent;
use App\Event\JobFailedEvent;
use App\Event\JobReadyEvent;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class NoPayloadEventCallbackFactory extends AbstractEventCallbackFactory
{
    /**
     * @var array<class-string, CallbackInterface::TYPE_*>
     */
    private const EVENT_TO_CALLBACK_TYPE_MAP = [
        JobReadyEvent::class => CallbackInterface::TYPE_JOB_STARTED,
        CompilationCompletedEvent::class => CallbackInterface::TYPE_COMPILATION_SUCCEEDED,
        ExecutionStartedEvent::class => CallbackInterface::TYPE_EXECUTION_STARTED,
        ExecutionCompletedEvent::class => CallbackInterface::TYPE_EXECUTION_COMPLETED,
        JobCompletedEvent::class => CallbackInterface::TYPE_JOB_COMPLETED,
        JobFailedEvent::class => CallbackInterface::TYPE_JOB_FAILED,
    ];

    public function handles(Event $event): bool
    {
        return
            $event instanceof JobReadyEvent ||
            $event instanceof CompilationCompletedEvent ||
            $event instanceof ExecutionStartedEvent ||
            $event instanceof ExecutionCompletedEvent ||
            $event instanceof JobCompletedEvent ||
            $event instanceof JobFailedEvent
            ;
    }

    public function createForEvent(Event $event): ?CallbackInterface
    {
        if ($this->handles($event)) {
            return $this->create(self::EVENT_TO_CALLBACK_TYPE_MAP[$event::class], []);
        }

        return null;
    }
}
