<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use App\Event\TestFinishedEvent;
use App\Event\TestStartedEvent;
use App\Event\TestStepFailedEvent;
use App\Event\TestStepPassedEvent;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class TestEventCallbackFactory extends AbstractEventCallbackFactory
{
    /**
     * @var array<class-string, CallbackInterface::TYPE_*>
     */
    private const EVENT_TO_CALLBACK_TYPE_MAP = [
        TestStartedEvent::class => CallbackInterface::TYPE_TEST_STARTED,
        TestStepPassedEvent::class => CallbackInterface::TYPE_STEP_PASSED,
        TestStepFailedEvent::class => CallbackInterface::TYPE_STEP_FAILED,
        TestFinishedEvent::class => CallbackInterface::TYPE_TEST_FINISHED,
    ];

    public function handles(Event $event): bool
    {
        return
            $event instanceof TestStartedEvent ||
            $event instanceof TestStepPassedEvent ||
            $event instanceof TestStepFailedEvent ||
            $event instanceof TestFinishedEvent;
    }

    public function createForEvent(Event $event): ?CallbackInterface
    {
        if (
            $event instanceof TestStartedEvent ||
            $event instanceof TestStepPassedEvent ||
            $event instanceof TestStepFailedEvent ||
            $event instanceof TestFinishedEvent
        ) {
            $document = $event->getDocument();

            $documentData = $document->parse();
            $documentData = is_array($documentData) ? $documentData : [];

            return $this->create(self::EVENT_TO_CALLBACK_TYPE_MAP[$event::class], $documentData);
        }

        return null;
    }
}
