<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Services\CallbackEventFactory;
use webignition\BasilCompilerModels\ErrorOutputInterface;

class TestCallbackEventFactory
{
    public function __construct(private CallbackEventFactory $callbackEventFactory)
    {
    }

    /**
     * @param string $source
     * @param array<mixed> $errorOutputData
     *
     * @return SourceCompileFailureEvent
     */
    public function createSourceCompileFailureEvent(
        string $source,
        array $errorOutputData
    ): SourceCompileFailureEvent {
        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $errorOutput
            ->shouldReceive('getData')
            ->andReturn($errorOutputData);

        return $this->callbackEventFactory->createSourceCompileFailureEvent($source, $errorOutput);
    }

    public function createEmptyPayloadSourceCompileFailureEvent(): SourceCompileFailureEvent
    {
        return $this->createSourceCompileFailureEvent('/app/source/Test/test.yml', []);
    }
}
