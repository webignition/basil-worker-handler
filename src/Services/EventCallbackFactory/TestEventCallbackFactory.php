<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use App\Event\TestStartedEvent;
use App\Event\TestStepFailedEvent;
use App\Event\TestStepPassedEvent;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\CallbackFactory as PersistenceBundleCallbackFactory;

class TestEventCallbackFactory implements EventCallbackFactoryInterface
{
    /**
     * @var array<class-string, CallbackInterface::TYPE_*>
     */
    private const EVENT_TO_CALLBACK_TYPE_MAP = [
        TestStartedEvent::class => CallbackInterface::TYPE_TEST_STARTED,
        TestStepPassedEvent::class => CallbackInterface::TYPE_STEP_PASSED,
        TestStepFailedEvent::class => CallbackInterface::TYPE_STEP_FAILED,
    ];

    public function __construct(
        private PersistenceBundleCallbackFactory $persistenceBundleCallbackFactory
    ) {
    }

    public function handles(Event $event): bool
    {
        return
            $event instanceof TestStartedEvent ||
            $event instanceof TestStepPassedEvent ||
            $event instanceof TestStepFailedEvent;
    }

    public function create(Event $event): ?CallbackInterface
    {
        if (
            $event instanceof TestStartedEvent ||
            $event instanceof TestStepPassedEvent ||
            $event instanceof TestStepFailedEvent
        ) {
            $document = $event->getDocument();

            $documentData = $document->parse();
            $documentData = is_array($documentData) ? $documentData : [];

            return $this->persistenceBundleCallbackFactory->create(
                self::EVENT_TO_CALLBACK_TYPE_MAP[$event::class],
                $documentData
            );
        }

        return null;
    }
}
