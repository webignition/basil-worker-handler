<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Model\Callback\CompileFailureCallback;
use App\Model\Callback\ExecuteDocumentReceivedCallback;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\EntityPersister;
use webignition\YamlDocument\Document;

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

    public function createTestExecuteDocumentReceivedEvent(
        Test $test,
        Document $document
    ): TestExecuteDocumentReceivedEvent {
        $callback = new ExecuteDocumentReceivedCallback($document);
        $this->entityPersister->persist($callback->getEntity());

        return new TestExecuteDocumentReceivedEvent($test, $document, $callback);
    }
}
