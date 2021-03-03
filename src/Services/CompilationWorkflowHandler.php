<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\JobReadyEvent;
use App\Event\SourceCompilation\SourceCompilationPassedEvent;
use App\Message\CompileSourceMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use webignition\BasilWorker\StateBundle\Services\CompilationState;

class CompilationWorkflowHandler implements EventSubscriberInterface
{
    public function __construct(
        private CompilationState $compilationState,
        private MessageBusInterface $messageBus,
        private SourcePathFinder $sourcePathFinder
    ) {
    }

    /**
     * @return array[][]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SourceCompilationPassedEvent::class => [
                ['dispatchNextCompileSourceMessage', 50],
            ],
            JobReadyEvent::class => [
                ['dispatchNextCompileSourceMessage', 50],
            ],
        ];
    }

    public function dispatchNextCompileSourceMessage(): void
    {
        if (!in_array($this->compilationState, CompilationState::FINISHED_STATES)) {
            $sourcePath = $this->sourcePathFinder->findNextNonCompiledPath();

            if (is_string($sourcePath)) {
                $this->messageBus->dispatch(new CompileSourceMessage($sourcePath));
            }
        }
    }
}
