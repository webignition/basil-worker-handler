<?php

declare(strict_types=1);

namespace App\Services;

class SourcePathTranslator
{
    private string $compilerSourceDirectory;
    private int $compilerSourceDirectoryLength;
    private string $compilerTargetDirectory;
    private int $compilerTargetDirectoryLength;

    public function __construct(string $compilerSourceDirectory, string $compilerTargetDirectory)
    {
        $this->compilerSourceDirectory = $compilerSourceDirectory;
        $this->compilerSourceDirectoryLength = strlen($compilerSourceDirectory);
        $this->compilerTargetDirectory = $compilerTargetDirectory;
        $this->compilerTargetDirectoryLength = strlen($compilerTargetDirectory);
    }

    public function stripCompilerSourceDirectory(string $path): string
    {
        if (false === $this->isPrefixedWith($path, $this->compilerSourceDirectory)) {
            return $path;
        }

        $path = substr($path, $this->compilerSourceDirectoryLength);
        return ltrim($path, '/');
    }

    public function stripCompilerTargetDirectory(string $path): string
    {
        if (false === $this->isPrefixedWith($path, $this->compilerTargetDirectory)) {
            return $path;
        }

        $path = substr($path, $this->compilerTargetDirectoryLength);
        return ltrim($path, '/');
    }

    private function isPrefixedWith(string $path, string $prefix): bool
    {
        $prefixLength = strlen($prefix);

        if (strlen($path) < $prefixLength) {
            return false;
        }

        $pathPrefix = substr($path, 0, $prefixLength);
        if ($pathPrefix !== $prefix) {
            return false;
        }

        return true;
    }
}
