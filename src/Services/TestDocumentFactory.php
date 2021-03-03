<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\RunnerTest\TestProxy;
use webignition\BasilWorker\PersistenceBundle\Entity\Test as TestEntity;
use webignition\YamlDocument\Document;
use webignition\YamlDocumentGenerator\YamlGenerator;

class TestDocumentFactory
{
    public function __construct(
        private YamlGenerator $yamlGenerator,
        private TestDocumentMutator $testDocumentMutator
    ) {
    }

    public function create(TestEntity $test): Document
    {
        $runnerTest = new TestProxy($test);
        $runnerTestString = $this->yamlGenerator->generate($runnerTest);

        return $this->testDocumentMutator->removeCompilerSourceDirectoryFromSource(new Document($runnerTestString));
    }
}
