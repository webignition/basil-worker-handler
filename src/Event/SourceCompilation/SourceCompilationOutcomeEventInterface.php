<?php

declare(strict_types=1);

namespace App\Event\SourceCompilation;

use Psr\EventDispatcher\StoppableEventInterface;
use webignition\BasilCompilerModels\OutputInterface;

interface SourceCompilationOutcomeEventInterface extends StoppableEventInterface
{
    public function getSource(): string;
    public function getOutput(): OutputInterface;
}
