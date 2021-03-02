<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Event\CallbackHttpErrorEvent;
use App\Event\JobCompletedEvent;
use App\Event\JobReadyEvent;
use App\Event\JobTimeoutEvent;
use App\Event\SourceCompilation\SourceCompilationFailedEvent;
use App\Event\SourceCompilation\SourceCompilationPassedEvent;
use App\Event\SourceCompilation\SourceCompilationStartedEvent;
use App\Event\TestStartedEvent;
use App\Event\TestStepFailedEvent;
use App\Event\TestStepPassedEvent;
use App\Message\SendCallbackMessage;
use App\Model\Callback\StampedCallbackInterface;
use App\Services\CallbackFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\CallbackStateMutator;

class SendCallbackMessageDispatcher implements EventSubscriberInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private CallbackStateMutator $callbackStateMutator,
        private CallbackFactory $callbackFactory
    ) {
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
        $this->messageBus->dispatch($this->createCallbackEnvelope($callback));
    }

    /**
     * @param CallbackInterface $callback
     *
     * @return Envelope
     */
    private function createCallbackEnvelope(CallbackInterface $callback): Envelope
    {
        $sendCallbackMessage = new SendCallbackMessage((int) $callback->getId());
        $stamps = [];

        if ($callback instanceof StampedCallbackInterface) {
            $stampCollection = $callback->getStamps();
            if ($stampCollection->hasStamps()) {
                $stamps = $stampCollection->getStamps();
            }
        }

        return new Envelope($sendCallbackMessage, $stamps);
    }
}
