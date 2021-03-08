<?php

declare(strict_types=1);

namespace App\Tests\DataProvider\CallbackFactory;

use App\Event\JobFailedEvent;
use App\Tests\Mock\Entity\MockCallback;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

trait CreateFromJobFailedEventDataProviderTrait
{
    /**
     * @return array[]
     */
    public function createFromJobFailedEventDataProvider(): array
    {
        return [
            JobFailedEvent::class => [
                'event' => new JobFailedEvent(),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_JOB_FAILED)
                    ->withGetPayloadCall([])
                    ->getMock(),
            ],
        ];
    }
}
