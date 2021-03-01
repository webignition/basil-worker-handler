<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\TestExecuteDocumentReceivedEvent;
use App\Event\TestStartedEvent;
use App\Model\RunnerTest\TestProxy;
use Psr\EventDispatcher\EventDispatcherInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\TcpCliProxyClient\Client;
use webignition\TcpCliProxyClient\Handler;
use webignition\YamlDocument\Document;
use webignition\YamlDocumentGenerator\YamlGenerator;

class TestExecutor
{
    public function __construct(
        private Client $delegatorClient,
        private YamlDocumentFactory $yamlDocumentFactory,
        private EventDispatcherInterface $eventDispatcher,
        private YamlGenerator $yamlGenerator,
        private TestDocumentMutator $testDocumentMutator
    ) {
    }

    public function execute(Test $test): void
    {
        $delegatorClientHandler = new Handler();
        $delegatorClientHandler
            ->addCallback(function (string $buffer) {
                if (false === ctype_digit($buffer) && '' !== trim($buffer)) {
                    $this->yamlDocumentFactory->process($buffer);
                }
            });

        $runnerTest = new TestProxy($test);
        $runnerTestString = $this->yamlGenerator->generate($runnerTest);

        $this->eventDispatcher->dispatch(
            new TestStartedEvent(
                $test,
                $this->testDocumentMutator->removeCompilerSourceDirectoryFromSource(new Document($runnerTestString))
            )
        );

        $this->yamlDocumentFactory->setOnDocumentCreated(function (Document $document) use ($test) {
            $this->eventDispatcher->dispatch(new TestExecuteDocumentReceivedEvent($test, $document));
        });

        $this->yamlDocumentFactory->start();

        $this->delegatorClient->request(
            sprintf(
                './bin/delegator --browser %s %s',
                $test->getConfiguration()->getBrowser(),
                $test->getTarget()
            ),
            $delegatorClientHandler
        );

        $this->yamlDocumentFactory->stop();
    }
}
