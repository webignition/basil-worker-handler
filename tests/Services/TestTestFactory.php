<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Services\TestFactory;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration as ModelTestConfiguration;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Entity\TestConfiguration;
use webignition\BasilWorker\PersistenceBundle\Services\EntityPersister;

class TestTestFactory
{
    public function __construct(
        private TestFactory $testFactory,
        private EntityPersister $entityPersister
    ) {
    }

    /**
     * @param TestConfiguration $configuration
     * @param string $source
     * @param string $target
     * @param int $stepCount
     * @param Test::STATE_* $state
     *
     * @return Test
     */
    public function create(
        TestConfiguration $configuration,
        string $source,
        string $target,
        int $stepCount,
        string $state = Test::STATE_AWAITING
    ): Test {
        $tests = $this->testFactory->createFromManifestCollection([
            new TestManifest(
                new ModelTestConfiguration($configuration->getBrowser(), $configuration->getUrl()),
                $source,
                $target,
                $stepCount
            ),
        ]);

        $test = $tests[0];
        $test->setState($state);
        $this->entityPersister->persist($test);

        return $test;
    }
}
