<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\JobCompletedEvent;
use App\Event\TestFinishedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use webignition\BasilWorker\StateBundle\Services\ApplicationState;
use webignition\BasilWorker\StateBundle\Services\ExecutionState;

class ApplicationWorkflowHandler implements EventSubscriberInterface
{
    public function __construct(
        private ApplicationState $applicationState,
        private ExecutionState $executionState,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @return array<class-string, array<int, array<int, int|string>>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            TestFinishedEvent::class => [
                ['dispatchJobCompleteEvent', 0],
            ],
        ];
    }

    public function dispatchJobCompleteEvent(): void
    {
        if (
            $this->applicationState->is(ApplicationState::STATE_COMPLETE) ||
            $this->executionState->is(ExecutionState::STATE_COMPLETE)
        ) {
            $this->eventDispatcher->dispatch(new JobCompletedEvent());
        }
    }
}
