<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\BasilFixtureHandler;
use webignition\BasilWorker\PersistenceBundle\Entity\Source;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\SourceFactory;

class SourceCreationFactory
{
    public static function createFromManifestPath(string $manifestPath): InvokableInterface
    {
        $manifestContent = (string) file_get_contents($manifestPath);
        $sourcePaths = array_filter(explode("\n", $manifestContent));

        return new InvokableCollection([
            'create uploaded files' => new Invokable(
                function (BasilFixtureHandler $basilFixtureHandler, array $sourcePaths) {
                    $basilFixtureHandler->createUploadFileCollection($sourcePaths);
                },
                [
                    new ServiceReference(BasilFixtureHandler::class),
                    $sourcePaths,
                ]
            ),
            'create sources' => new Invokable(
                function (SourceFactory $sourceFactory, array $sourcePaths) {
                    foreach ($sourcePaths as $sourcePath) {
                        $sourceType = substr_count($sourcePath, 'Test/') === 0
                            ? Source::TYPE_RESOURCE
                            : Source::TYPE_TEST;

                        $sources[$sourcePath] = $sourceFactory->create($sourceType, $sourcePath);
                    }

                    return $sourcePaths;
                },
                [
                    new ServiceReference(SourceFactory::class),
                    $sourcePaths,
                ]
            ),
        ]);
    }
}
