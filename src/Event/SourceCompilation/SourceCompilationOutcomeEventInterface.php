<?php

declare(strict_types=1);

namespace App\Event\SourceCompilation;

use webignition\BasilCompilerModels\OutputInterface;

interface SourceCompilationOutcomeEventInterface extends SourceCompilationEventInterface
{
    public function getOutput(): OutputInterface;
}
