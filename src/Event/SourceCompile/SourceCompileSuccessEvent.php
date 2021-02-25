<?php

declare(strict_types=1);

namespace App\Event\SourceCompile;

use webignition\BasilCompilerModels\SuiteManifest;

class SourceCompileSuccessEvent extends AbstractSourceCompileEvent
{
    public function __construct(string $source, private SuiteManifest $suiteManifest)
    {
        parent::__construct($source);
    }

    public function getOutput(): SuiteManifest
    {
        return $this->suiteManifest;
    }
}
