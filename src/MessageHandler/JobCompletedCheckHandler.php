<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Event\JobCompletedEvent;
use App\Message\JobCompleteCheckMessage;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorker\StateBundle\Services\ApplicationState;

class JobCompletedCheckHandler implements MessageHandlerInterface
{
    public function __construct(
        private ApplicationState $applicationState,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function __invoke(JobCompleteCheckMessage $jobCompleteCheckMessage): void
    {
        if ($this->applicationState->is(ApplicationState::STATE_COMPLETE)) {
            $this->eventDispatcher->dispatch(new JobCompletedEvent());
        }
    }
}
