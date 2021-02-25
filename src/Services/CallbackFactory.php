<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Callback\CompileFailureCallback;
use App\Model\Callback\ExecuteDocumentReceivedCallback;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilWorker\PersistenceBundle\Services\EntityPersister;
use webignition\YamlDocument\Document;

class CallbackFactory
{
    public function __construct(private EntityPersister $entityPersister)
    {
    }

    public function createForCompileFailure(ErrorOutputInterface $errorOutput): CompileFailureCallback
    {
        $callback = new CompileFailureCallback($errorOutput);

        $this->entityPersister->persist($callback->getEntity());

        return $callback;
    }

    public function createForExecuteDocumentReceived(Document $document): ExecuteDocumentReceivedCallback
    {
        $callback = new ExecuteDocumentReceivedCallback($document);

        $this->entityPersister->persist($callback->getEntity());

        return $callback;
    }
}
