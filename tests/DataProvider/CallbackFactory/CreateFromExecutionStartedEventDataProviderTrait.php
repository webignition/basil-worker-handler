<?php

declare(strict_types=1);

namespace App\Tests\DataProvider\CallbackFactory;

use App\Event\ExecutionStartedEvent;
use App\Tests\Mock\Entity\MockCallback;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

trait CreateFromExecutionStartedEventDataProviderTrait
{
    /**
     * @return array[]
     */
    public function createFromExecutionStartedEventDataProvider(): array
    {
        return [
            ExecutionStartedEvent::class => [
                'event' => new ExecutionStartedEvent(),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_EXECUTION_STARTED)
                    ->withGetPayloadCall([])
                    ->getMock(),
            ],
        ];
    }
}
