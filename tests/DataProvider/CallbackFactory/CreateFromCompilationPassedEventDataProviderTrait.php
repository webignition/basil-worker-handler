<?php

declare(strict_types=1);

namespace App\Tests\DataProvider\CallbackFactory;

use App\Event\SourceCompilation\SourceCompilationFailedEvent;
use App\Event\SourceCompilation\SourceCompilationPassedEvent;
use App\Tests\Mock\Entity\MockCallback;
use App\Tests\Mock\MockSuiteManifest;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

trait CreateFromCompilationPassedEventDataProviderTrait
{
    /**
     * @return array[]
     */
    public function createFromCompilationPassedEventDataProvider(): array
    {
        return [
            SourceCompilationFailedEvent::class => [
                'event' => new SourceCompilationPassedEvent(
                    '/app/source/test.yml',
                    (new MockSuiteManifest())->getMock()
                ),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_COMPILATION_PASSED)
                    ->withGetPayloadCall([
                        'source' => '/app/source/test.yml',
                    ])
                    ->getMock(),
            ],
        ];
    }
}
