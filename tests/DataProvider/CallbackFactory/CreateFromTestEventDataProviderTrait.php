<?php

declare(strict_types=1);

namespace App\Tests\DataProvider\CallbackFactory;

use App\Event\TestStartedEvent;
use App\Event\TestStepFailedEvent;
use App\Event\TestStepPassedEvent;
use App\Tests\Mock\Entity\MockCallback;
use App\Tests\Mock\Entity\MockTest;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\YamlDocument\Document;

trait CreateFromTestEventDataProviderTrait
{
    /**
     * @return array[]
     */
    public function createFromTestEventEventDataProvider(): array
    {
        $documentData = [
            'document-key' => 'document-value',
        ];

        $document = new Document((string) json_encode($documentData));

        return [
            TestStartedEvent::class => [
                'event' => new TestStartedEvent((new MockTest())->getMock(), $document),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_TEST_STARTED)
                    ->withGetPayloadCall($documentData)
                    ->getMock(),
            ],
            TestStepPassedEvent::class => [
                'event' => new TestStepPassedEvent((new MockTest())->getMock(), $document),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_STEP_PASSED)
                    ->withGetPayloadCall($documentData)
                    ->getMock(),
            ],
            TestStepFailedEvent::class => [
                'event' => new TestStepFailedEvent((new MockTest())->getMock(), $document),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_STEP_FAILED)
                    ->withGetPayloadCall($documentData)
                    ->getMock(),
            ],
        ];
    }
}
