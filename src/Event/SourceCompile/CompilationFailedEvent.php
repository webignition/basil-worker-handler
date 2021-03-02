<?php

declare(strict_types=1);

namespace App\Event\SourceCompile;

use webignition\BasilCompilerModels\ErrorOutputInterface;

class CompilationFailedEvent extends AbstractSourceCompileEvent
{
    public function __construct(string $source, private ErrorOutputInterface $errorOutput)
    {
        parent::__construct($source);
    }

    public function getOutput(): ErrorOutputInterface
    {
        return $this->errorOutput;
    }
}
