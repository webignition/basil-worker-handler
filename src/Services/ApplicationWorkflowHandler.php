<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\JobCompletedEvent;
use App\Event\TestExecuteCompleteEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use webignition\BasilWorker\StateBundle\Services\ApplicationState;

class ApplicationWorkflowHandler implements EventSubscriberInterface
{
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
            TestExecuteCompleteEvent::class => [
                ['dispatchJobCompleteEvent', 0],
            ],
        ];
    }

    public function dispatchJobCompleteEvent(): void
    {
        if (ApplicationState::STATE_COMPLETE === (string) $this->applicationState) {
            $this->eventDispatcher->dispatch(new JobCompletedEvent());
        }
    }
}
