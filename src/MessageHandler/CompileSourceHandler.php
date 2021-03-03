<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Event\SourceCompilation\SourceCompilationFailedEvent;
use App\Event\SourceCompilation\SourceCompilationPassedEvent;
use App\Message\CompileSourceMessage;
use App\Services\Compiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;
use webignition\BasilWorker\StateBundle\Services\CompilationState;

class CompileSourceHandler implements MessageHandlerInterface
{
    public function __construct(
        private Compiler $compiler,
        private JobStore $jobStore,
        private CompilationState $compilationState,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function __invoke(CompileSourceMessage $message): void
    {
        if (false === $this->jobStore->has()) {
            return;
        }

        if (false === in_array($this->compilationState, [CompilationState::STATE_RUNNING])) {
            return;
        }

        $sourcePath = $message->getPath();
        $output = $this->compiler->compile($sourcePath);

        $event = $output instanceof ErrorOutputInterface
            ? new SourceCompilationFailedEvent($sourcePath, $output)
            : new SourceCompilationPassedEvent($sourcePath, $output);

        $this->eventDispatcher->dispatch($event);
    }
}
