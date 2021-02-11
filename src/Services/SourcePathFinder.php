<?php

declare(strict_types=1);

namespace App\Services;

use webignition\BasilWorker\PersistenceBundle\Services\Repository\TestRepository;
use webignition\BasilWorker\PersistenceBundle\Services\Store\SourceStore;
use webignition\StringPrefixRemover\DefinedStringPrefixRemover;

class SourcePathFinder
{
    private TestRepository $testRepository;
    private SourceStore $sourceStore;
    private DefinedStringPrefixRemover $compilerSourcePathPrefixRemover;

    public function __construct(
        TestRepository $testRepository,
        SourceStore $sourceStore,
        DefinedStringPrefixRemover $compilerSourcePathPrefixRemover
    ) {
        $this->testRepository = $testRepository;
        $this->sourceStore = $sourceStore;
        $this->compilerSourcePathPrefixRemover = $compilerSourcePathPrefixRemover;
    }

    /**
     * @return string[]
     */
    public function findCompiledPaths(): array
    {
        $sources = $this->testRepository->findAllSources();

        return $this->removeCompilerSourceDirectoryPrefixFromPaths($sources);
    }

    public function findNextNonCompiledPath(): ?string
    {
        $sourcePaths = $this->sourceStore->findAllPaths();
        $testPaths = $this->testRepository->findAllSources();
        $testPaths = $this->removeCompilerSourceDirectoryPrefixFromPaths($testPaths);

        foreach ($sourcePaths as $sourcePath) {
            if (!in_array($sourcePath, $testPaths)) {
                return $sourcePath;
            }
        }

        return null;
    }

    /**
     * @param string[] $paths
     *
     * @return string[]
     */
    private function removeCompilerSourceDirectoryPrefixFromPaths(array $paths): array
    {
        $strippedPaths = [];

        foreach ($paths as $path) {
            if (is_string($path)) {
                $strippedPaths[] = $this->compilerSourcePathPrefixRemover->remove($path);
            }
        }

        return $strippedPaths;
    }
}
