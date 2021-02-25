<?php

declare(strict_types=1);

namespace App\Event\SourceCompile;

use App\Event\CallbackEventInterface;
use App\Model\Callback\CompileFailureCallback;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class SourceCompileFailureEvent extends AbstractSourceCompileEvent implements CallbackEventInterface
{
    public function __construct(
        string $source,
        private ErrorOutputInterface $errorOutput,
        private CompileFailureCallback $callback
    ) {
        parent::__construct($source);
    }

    public function getOutput(): ErrorOutputInterface
    {
        return $this->errorOutput;
    }

    public function getCallback(): CallbackInterface
    {
        return $this->callback;
    }
}
