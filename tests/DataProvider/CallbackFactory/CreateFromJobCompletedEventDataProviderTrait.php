<?php

declare(strict_types=1);

namespace App\Tests\DataProvider\CallbackFactory;

use App\Event\JobCompletedEvent;
use App\Tests\Mock\Entity\MockCallback;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

trait CreateFromJobCompletedEventDataProviderTrait
{
    /**
     * @return array[]
     */
    public function createFromJobCompletedEventDataProvider(): array
    {
        return [
            JobCompletedEvent::class => [
                'event' => new JobCompletedEvent(),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_JOB_COMPLETED)
                    ->withGetPayloadCall([])
                    ->getMock(),
            ],
        ];
    }
}
