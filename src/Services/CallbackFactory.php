<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\CallbackHttpErrorEvent;
use App\Event\JobCompleteEvent;
use App\Event\JobTimeoutEvent;
use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\TestExecuteDocumentReceivedEvent;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\CallbackFactory as PersistenceBundleCallbackFactory;

class CallbackFactory
{
    public function __construct(
        private PersistenceBundleCallbackFactory $persistenceBundleCallbackFactory
    ) {
    }

    public function createForEvent(Event $event): ?CallbackInterface
    {
        if ($event instanceof SourceCompileFailureEvent) {
            return $this->persistenceBundleCallbackFactory->create(
                CallbackInterface::TYPE_COMPILE_FAILURE,
                $event->getOutput()->getData()
            );
        }

        if ($event instanceof CallbackHttpErrorEvent) {
            return $event->getCallback();
        }

        if ($event instanceof TestExecuteDocumentReceivedEvent) {
            $document = $event->getDocument();

            $documentData = $document->parse();
            $documentData = is_array($documentData) ? $documentData : [];

            return $this->persistenceBundleCallbackFactory->create(
                CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED,
                $documentData
            );
        }

        if ($event instanceof JobTimeoutEvent) {
            return $this->persistenceBundleCallbackFactory->create(
                CallbackInterface::TYPE_JOB_TIMEOUT,
                [
                    'maximum_duration_in_seconds' => $event->getJobMaximumDuration(),
                ]
            );
        }

        if ($event instanceof JobCompleteEvent) {
            return $this->persistenceBundleCallbackFactory->create(CallbackInterface::TYPE_JOB_COMPLETE, []);
        }

        return null;
    }
}
