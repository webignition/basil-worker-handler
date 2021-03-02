<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\SourceCompilation\SourceCompilationPassedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Entity\TestConfiguration;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\TestFactory as BundleTestFactory;

class TestFactory implements EventSubscriberInterface
{
    public function __construct(private BundleTestFactory $bundleTestFactory)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            SourceCompilationPassedEvent::class => [
                ['createFromSourceCompileSuccessEvent', 100],
            ],
        ];
    }

    /**
     * @param SourceCompilationPassedEvent $event
     *
     * @return Test[]
     */
    public function createFromSourceCompileSuccessEvent(SourceCompilationPassedEvent $event): array
    {
        $suiteManifest = $event->getOutput();

        return $this->createFromManifestCollection($suiteManifest->getTestManifests());
    }

    /**
     * @param TestManifest[] $manifests
     *
     * @return Test[]
     */
    public function createFromManifestCollection(array $manifests): array
    {
        $tests = [];

        foreach ($manifests as $manifest) {
            if ($manifest instanceof TestManifest) {
                $tests[] = $this->createFromManifest($manifest);
            }
        }

        return $tests;
    }

    private function createFromManifest(TestManifest $manifest): Test
    {
        $manifestConfiguration = $manifest->getConfiguration();

        return $this->bundleTestFactory->create(
            TestConfiguration::create(
                $manifestConfiguration->getBrowser(),
                $manifestConfiguration->getUrl()
            ),
            $manifest->getSource(),
            $manifest->getTarget(),
            $manifest->getStepCount()
        );
    }
}
