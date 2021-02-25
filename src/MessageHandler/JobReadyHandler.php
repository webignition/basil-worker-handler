<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Event\JobReadyEvent;
use App\Message\JobReadyMessage;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class JobReadyHandler implements MessageHandlerInterface
{
    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function __invoke(JobReadyMessage $message): void
    {
        $this->eventDispatcher->dispatch(new JobReadyEvent());
    }
}
