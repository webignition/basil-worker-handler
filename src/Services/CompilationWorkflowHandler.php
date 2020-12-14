<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\JobReadyEvent;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Message\CompileSourceMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class CompilationWorkflowHandler implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private SourcePathFinder $sourcePathFinder;

    public function __construct(MessageBusInterface $messageBus, SourcePathFinder $sourcePathFinder)
    {
        $this->messageBus = $messageBus;
        $this->sourcePathFinder = $sourcePathFinder;
    }

    /**
     * @return array[][]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SourceCompileSuccessEvent::class => [
                ['dispatchNextCompileSourceMessage', 50],
            ],
            JobReadyEvent::class => [
                ['dispatchNextCompileSourceMessage', 50],
            ],
        ];
    }

    public function dispatchNextCompileSourceMessage(): void
    {
        $nextNonCompiledSource = $this->sourcePathFinder->findNextNonCompiledPath();

        if (is_string($nextNonCompiledSource)) {
            $message = new CompileSourceMessage($nextNonCompiledSource);
            $this->messageBus->dispatch($message);
        }
    }
}
