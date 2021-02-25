<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\SourceCompile\SourceCompileEventInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use webignition\BasilCompilerModels\OutputInterface;

class SourceCompileEventDispatcher
{
    public function __construct(
        private SourceCompileEventFactory $factory,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function dispatch(string $source, OutputInterface $output): void
    {
        $event = $this->factory->create($source, $output);
        if ($event instanceof SourceCompileEventInterface) {
            $this->dispatcher->dispatch($event);
        }
    }
}
