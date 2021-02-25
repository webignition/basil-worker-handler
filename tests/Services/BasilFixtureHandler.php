<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class BasilFixtureHandler
{
    public function __construct(
        private string $fixturesPath,
        private string $uploadedPath
    ) {
    }

    public function createUploadedFile(string $relativePath): UploadedFile
    {
        $fixturePath = $this->fixturesPath . '/' . $relativePath;
        $uploadedFilePath = $this->uploadedPath . '/' . $relativePath;

        if (!file_exists($uploadedFilePath)) {
            $directory = dirname($uploadedFilePath);
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            copy($fixturePath, $uploadedFilePath);
        }

        return new UploadedFile($uploadedFilePath, '', 'text/yaml', null, true);
    }

    /**
     * @param string[] $relativePaths
     *
     * @return UploadedFile[]
     */
    public function createUploadFileCollection(array $relativePaths): array
    {
        $uploadedFiles = [];

        foreach ($relativePaths as $relativePath) {
            $uploadedFiles[$relativePath] = $this->createUploadedFile($relativePath);
        }

        return $uploadedFiles;
    }
}
