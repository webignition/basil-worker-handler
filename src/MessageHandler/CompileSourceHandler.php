<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CompileSourceMessage;
use App\Services\CompilationState;
use App\Services\Compiler;
use App\Services\SourceCompileEventDispatcher;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;

class CompileSourceHandler implements MessageHandlerInterface
{
    private Compiler $compiler;
    private JobStore $jobStore;
    private SourceCompileEventDispatcher $eventDispatcher;
    private CompilationState $compilationState;

    public function __construct(
        Compiler $compiler,
        JobStore $jobStore,
        SourceCompileEventDispatcher $eventDispatcher,
        CompilationState $compilationState
    ) {
        $this->compiler = $compiler;
        $this->jobStore = $jobStore;
        $this->eventDispatcher = $eventDispatcher;
        $this->compilationState = $compilationState;
    }

    public function __invoke(CompileSourceMessage $message): void
    {
        if (false === $this->jobStore->has()) {
            return;
        }

        if (false === $this->compilationState->is(CompilationState::STATE_RUNNING)) {
            return;
        }

        $sourcePath = $message->getPath();
        $output = $this->compiler->compile($sourcePath);

        $this->eventDispatcher->dispatch($sourcePath, $output);
    }
}
