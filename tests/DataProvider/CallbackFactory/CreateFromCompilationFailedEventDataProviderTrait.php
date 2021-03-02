<?php

declare(strict_types=1);

namespace App\Tests\DataProvider\CallbackFactory;

use App\Event\SourceCompilation\CompilationFailedEvent;
use App\Tests\Mock\Entity\MockCallback;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

trait CreateFromCompilationFailedEventDataProviderTrait
{
    /**
     * @return array[]
     */
    public function createFromCompilationFailedEventDataProvider(): array
    {
        $errorOutputData = [
            'error-output-key' => 'error-output-value',
        ];

        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $errorOutput
            ->shouldReceive('getData')
            ->andReturn($errorOutputData);

        return [
            CompilationFailedEvent::class => [
                'event' => new CompilationFailedEvent(
                    '/app/source/test.yml',
                    $errorOutput
                ),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_COMPILATION_FAILED)
                    ->withGetPayloadCall($errorOutputData)
                    ->getMock(),
            ],
        ];
    }
}
