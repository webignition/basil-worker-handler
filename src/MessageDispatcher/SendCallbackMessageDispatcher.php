<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Event\CallbackHttpErrorEvent;
use App\Event\CompilationCompletedEvent;
use App\Event\ExecutionCompletedEvent;
use App\Event\ExecutionStartedEvent;
use App\Event\JobCompletedEvent;
use App\Event\JobFailedEvent;
use App\Event\JobReadyEvent;
use App\Event\JobTimeoutEvent;
use App\Event\SourceCompilation\SourceCompilationFailedEvent;
use App\Event\SourceCompilation\SourceCompilationPassedEvent;
use App\Event\SourceCompilation\SourceCompilationStartedEvent;
use App\Event\TestFailedEvent;
use App\Event\TestPassedEvent;
use App\Event\TestStartedEvent;
use App\Event\TestStepFailedEvent;
use App\Event\TestStepPassedEvent;
use App\Message\SendCallbackMessage;
use App\Services\CallbackFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\CallbackStateMutator;
use webignition\SymfonyMessengerMessageDispatcher\MessageDispatcher;

class SendCallbackMessageDispatcher extends AbstractMessageDispatcher implements EventSubscriberInterface
{
    public function __construct(
        MessageDispatcher $messageDispatcher,
        private CallbackStateMutator $callbackStateMutator,
        private CallbackFactory $callbackFactory
    ) {
        parent::__construct($messageDispatcher);
    }

    public static function getSubscribedEvents()
    {
        return [
            JobReadyEvent::class => [
                ['dispatchForEvent', 500],
            ],
            CallbackHttpErrorEvent::class => [
                ['dispatchForCallbackHttpErrorEvent', 0],
            ],
            SourceCompilationStartedEvent::class => [
                ['dispatchForEvent', 0],
            ],
            SourceCompilationPassedEvent::class => [
                ['dispatchForEvent', 500],
            ],
            SourceCompilationFailedEvent::class => [
                ['dispatchForEvent', 0],
            ],
            CompilationCompletedEvent::class => [
                ['dispatchForEvent', 100],
            ],
            ExecutionStartedEvent::class => [
                ['dispatchForEvent', 0],
            ],
            ExecutionCompletedEvent::class => [
                ['dispatchForEvent', 50],
            ],
            JobTimeoutEvent::class => [
                ['dispatchForEvent', 0],
            ],
            JobCompletedEvent::class => [
                ['dispatchForEvent', 0],
            ],
            TestStartedEvent::class => [
                ['dispatchForEvent', 0],
            ],
            TestStepPassedEvent::class => [
                ['dispatchForEvent', 0],
            ],
            TestStepFailedEvent::class => [
                ['dispatchForEvent', 0],
            ],
            TestPassedEvent::class => [
                ['dispatchForEvent', 100],
            ],
            TestFailedEvent::class => [
                ['dispatchForEvent', 0],
            ],
            JobFailedEvent::class => [
                ['dispatchForEvent', 0],
            ],
        ];
    }

    public function dispatchForEvent(Event $event): void
    {
        $callback = $this->callbackFactory->createForEvent($event);
        if ($callback instanceof CallbackInterface) {
            $this->dispatch($callback);
        }
    }

    public function dispatchForCallbackHttpErrorEvent(CallbackHttpErrorEvent $event): void
    {
        $this->dispatch($event->getCallback());
    }

    private function dispatch(CallbackInterface $callback): void
    {
        $this->callbackStateMutator->setQueued($callback);

        $this->doDispatch(new SendCallbackMessage((int) $callback->getId(), $callback->getRetryCount()));
    }
}
