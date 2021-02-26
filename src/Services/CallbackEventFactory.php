<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Model\Callback\CompileFailureCallback;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilWorker\PersistenceBundle\Services\EntityPersister;

class CallbackEventFactory
{
    public function __construct(private EntityPersister $entityPersister)
    {
    }

    public function createSourceCompileFailureEvent(
        string $source,
        ErrorOutputInterface $errorOutput
    ): SourceCompileFailureEvent {
        $callback = new CompileFailureCallback($errorOutput);
        $this->entityPersister->persist($callback->getEntity());

        return new SourceCompileFailureEvent($source, $errorOutput, $callback);
    }
}
