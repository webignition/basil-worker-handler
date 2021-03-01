<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\JobCompleteEvent;
use App\Event\JobTimeoutEvent;
use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Services\CallbackFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Entity\MockCallback;
use App\Tests\Mock\Entity\MockTest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\YamlDocument\Document;

class CallbackFactoryTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

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
        $errorOutputData = [
            'error-output-key' => 'error-output-value',
        ];

        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $errorOutput
            ->shouldReceive('getData')
            ->andReturn($errorOutputData);

        $documentData = [
            'document-key' => 'document-value',
        ];

        $document = new Document((string) json_encode($documentData));

        return [
            SourceCompileFailureEvent::class => [
                'event' => new SourceCompileFailureEvent(
                    '/app/source/test.yml',
                    $errorOutput
                ),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_COMPILE_FAILURE)
                    ->withGetPayloadCall($errorOutputData)
                    ->getMock(),
            ],
            TestExecuteDocumentReceivedEvent::class => [
                'event' => new TestExecuteDocumentReceivedEvent(
                    (new MockTest())->getMock(),
                    $document
                ),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED)
                    ->withGetPayloadCall($documentData)
                    ->getMock(),
            ],
            JobTimeoutEvent::class => [
                'event' => new JobTimeoutEvent(150),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_JOB_TIMEOUT)
                    ->withGetPayloadCall([
                        'maximum_duration_in_seconds' => 150,
                    ])
                    ->getMock(),
            ],
            JobCompleteEvent::class => [
                'event' => new JobCompleteEvent(),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_JOB_COMPLETE)
                    ->withGetPayloadCall([])
                    ->getMock(),
            ],
        ];
    }
}
