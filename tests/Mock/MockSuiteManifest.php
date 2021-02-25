<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Mockery\MockInterface;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\BasilCompilerModels\TestManifest;

class MockSuiteManifest
{
    private SuiteManifest $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(SuiteManifest::class);
    }

    public function getMock(): SuiteManifest
    {
        return $this->mock;
    }

    /**
     * @param TestManifest[] $testManifests
     */
    public function withGetTestManifestsCall(array $testManifests): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('getTestManifests')
            ->andReturn($testManifests);

        return $this;
    }
}
