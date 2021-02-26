<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Event\CallbackEventInterface;
use App\Event\CallbackHttpErrorEvent;
use App\Event\JobCompleteEvent;
use App\Event\JobTimeoutEvent;
use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Message\SendCallbackMessage;
use App\Model\Callback\StampedCallbackInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\CallbackStateMutator;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\CallbackFactory;

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
            CallbackHttpErrorEvent::class => [
                ['dispatchForCallbackEvent', 0],
            ],
            SourceCompileFailureEvent::class => [
                ['dispatchForCallbackEvent', 0],
            ],
            TestExecuteDocumentReceivedEvent::class => [
                ['dispatchForTextExecuteDocumentReceivedEvent', 0],
            ],
            JobTimeoutEvent::class => [
                ['dispatchForJobTimeoutEvent', 0],
            ],
            JobCompleteEvent::class => [
                ['dispatchForJobCompleteEvent', 0],
            ],
        ];
    }

    public function dispatchForCallbackEvent(CallbackEventInterface $event): void
    {
        $callback = $event->getCallback();

        $this->callbackStateMutator->setQueued($callback);
        $this->messageBus->dispatch($this->createCallbackEnvelope($callback));
    }

    public function dispatchForTextExecuteDocumentReceivedEvent(TestExecuteDocumentReceivedEvent $event): void
    {
        $document = $event->getDocument();

        $documentData = $document->parse();
        $documentData = is_array($documentData) ? $documentData : [];

        $this->createAndDispatchCallback(CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED, $documentData);
    }

    public function dispatchForJobTimeoutEvent(JobTimeoutEvent $event): void
    {
        $this->createAndDispatchCallback(CallbackInterface::TYPE_JOB_TIMEOUT, []);
    }

    public function dispatchForJobCompleteEvent(JobCompleteEvent $jobCompleteEvent): void
    {
        $this->createAndDispatchCallback(CallbackInterface::TYPE_JOB_COMPLETE, []);
    }

    /**
     * @param CallbackInterface::TYPE_* $type
     * @param array<mixed> $payload
     */
    private function createAndDispatchCallback(string $type, array $payload): void
    {
        $this->dispatchCallback(
            $this->callbackFactory->create(
                $type,
                $payload
            )
        );
    }

    private function dispatchCallback(CallbackInterface $callback): void
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
