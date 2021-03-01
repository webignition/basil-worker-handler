<?php

declare(strict_types=1);

namespace App\Services;

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
                CallbackInterface::TYPE_COMPILATION_FAILED,
                $event->getOutput()->getData()
            );
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
                CallbackInterface::TYPE_JOB_TIME_OUT,
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
