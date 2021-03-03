<?php

declare(strict_types=1);

namespace App\Tests\DataProvider\CallbackFactory;

use App\Event\CompilationCompletedEvent;
use App\Tests\Mock\Entity\MockCallback;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

trait CreateFromCompilationCompletedEventDataProviderTrait
{
    /**
     * @return array[]
     */
    public function createFromCompilationCompletedEventDataProvider(): array
    {
        return [
            CompilationCompletedEvent::class => [
                'event' => new CompilationCompletedEvent(),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_COMPILATION_SUCCEEDED)
                    ->withGetPayloadCall([])
                    ->getMock(),
            ],
        ];
    }
}
