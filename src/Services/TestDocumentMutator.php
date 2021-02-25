<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Document\Test;
use Symfony\Component\Yaml\Dumper;
use webignition\StringPrefixRemover\DefinedStringPrefixRemover;
use webignition\YamlDocument\Document;

class TestDocumentMutator
{
    public function __construct(
        private Dumper $yamlDumper,
        private DefinedStringPrefixRemover $compilerSourcePathPrefixRemover
    ) {
    }

    public function removeCompilerSourceDirectoryFromSource(Document $document): Document
    {
        $test = new Test($document);
        if ($test->isTest()) {
            $path = $test->getPath();

            $mutatedPath = $this->compilerSourcePathPrefixRemover->remove($path);
            if ($mutatedPath !== $path) {
                $mutatedPath = ltrim($mutatedPath, '/');
            }

            $mutatedTestSource = $this->yamlDumper->dump($test->getMutatedData([
                Test::KEY_PATH => $mutatedPath,
            ]));

            $document = new Document($mutatedTestSource);
        }

        return $document;
    }
}
