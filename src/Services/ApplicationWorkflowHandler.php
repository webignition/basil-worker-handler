<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\JobCompletedEvent;
use App\Event\JobFailedEvent;
use App\Event\TestFailedEvent;
use App\Event\TestPassedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use webignition\BasilWorker\StateBundle\Services\ApplicationState;

class ApplicationWorkflowHandler implements EventSubscriberInterface
{
    private const TEST_EVENT_TO_JOB_EVENT_MAP = [
        TestPassedEvent::class => JobCompletedEvent::class,
        TestFailedEvent::class => JobFailedEvent::class,
    ];

    public function __construct(
        private ApplicationState $applicationState,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @return array<class-string, array<int, array<int, int|string>>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            TestPassedEvent::class => [
                ['dispatchJobEndedEvent', 0],
            ],
            TestFailedEvent::class => [
                ['dispatchJobEndedEvent', 0],
            ],
        ];
    }

    public function dispatchJobEndedEvent(TestPassedEvent | TestFailedEvent $event): void
    {
        if ($this->applicationState->is(ApplicationState::STATE_COMPLETE)) {
            $this->eventDispatcher->dispatch(
                new (self::TEST_EVENT_TO_JOB_EVENT_MAP[$event::class])()
            );
        }
    }
}
