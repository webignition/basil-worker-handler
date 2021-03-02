<?php

declare(strict_types=1);

namespace App\Event\SourceCompilation;

use Psr\EventDispatcher\StoppableEventInterface;
use webignition\BasilCompilerModels\OutputInterface;

interface SourceCompileEventInterface extends StoppableEventInterface
{
    public function getSource(): string;
    public function getOutput(): OutputInterface;
}
