<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Event\JobTimeoutEvent;
use App\Message\TimeoutCheckMessage;
use App\MessageDispatcher\TimeoutCheckMessageDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;

class TimeoutCheckHandler implements MessageHandlerInterface
{
    public function __construct(
        private JobStore $jobStore,
        private TimeoutCheckMessageDispatcher $timeoutCheckMessageDispatcher,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function __invoke(TimeoutCheckMessage $timeoutCheck): void
    {
        if (false === $this->jobStore->has()) {
            return;
        }

        $job = $this->jobStore->get();
        if ($job->hasReachedMaximumDuration()) {
            $this->eventDispatcher->dispatch(new JobTimeoutEvent($job->getMaximumDurationInSeconds()));
        } else {
            $this->timeoutCheckMessageDispatcher->dispatch();
        }
    }
}
