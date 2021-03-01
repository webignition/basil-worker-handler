<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\JobCompleteEvent;
use App\Event\JobTimeoutEvent;
use App\Event\TestStartedEvent;
use App\Event\TestStepFailedEvent;
use App\Event\TestStepPassedEvent;
use App\Services\CallbackFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\DataProvider\CallbackFactory\CreateFromCompilationFailedEventDataProviderTrait;
use App\Tests\Mock\Entity\MockCallback;
use App\Tests\Mock\Entity\MockTest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\YamlDocument\Document;

class CallbackFactoryTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;
    use CreateFromCompilationFailedEventDataProviderTrait;

    private CallbackFactory $callbackFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testCreateForEventUnsupportedEvent(): void
    {
        self::assertNull($this->callbackFactory->createForEvent(new Event()));
    }

    /**
     * @dataProvider createForEventDataProvider
     * @dataProvider createFromCompilationFailedEventDataProvider
     */
    public function testCreateForEvent(Event $event, CallbackInterface $expectedCallback): void
    {
        $callback = $this->callbackFactory->createForEvent($event);

        self::assertInstanceOf(CallbackInterface::class, $callback);

        if ($callback instanceof CallbackInterface) {
            self::assertNotNull($callback->getId());
            self::assertSame($expectedCallback->getType(), $callback->getType());
            self::assertSame($expectedCallback->getPayload(), $callback->getPayload());
        }
    }

    /**
     * @return array[]
     */
    public function createForEventDataProvider(): array
    {
        $documentData = [
            'document-key' => 'document-value',
        ];

        $document = new Document((string) json_encode($documentData));

        return [
            TestStartedEvent::class => [
                'event' => new TestStartedEvent(
                    (new MockTest())->getMock(),
                    $document
                ),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_TEST_STARTED)
                    ->withGetPayloadCall($documentData)
                    ->getMock(),
            ],
            TestStepPassedEvent::class => [
                'event' => new TestStepPassedEvent(
                    (new MockTest())->getMock(),
                    $document
                ),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_STEP_PASSED)
                    ->withGetPayloadCall($documentData)
                    ->getMock(),
            ],
            TestStepFailedEvent::class => [
                'event' => new TestStepFailedEvent(
                    (new MockTest())->getMock(),
                    $document
                ),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_STEP_FAILED)
                    ->withGetPayloadCall($documentData)
                    ->getMock(),
            ],
            JobTimeoutEvent::class => [
                'event' => new JobTimeoutEvent(150),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_JOB_TIME_OUT)
                    ->withGetPayloadCall([
                        'maximum_duration_in_seconds' => 150,
                    ])
                    ->getMock(),
            ],
            JobCompleteEvent::class => [
                'event' => new JobCompleteEvent(),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_JOB_COMPLETED)
                    ->withGetPayloadCall([])
                    ->getMock(),
            ],
        ];
    }
}
