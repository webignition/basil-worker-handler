<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\JobCompleteEvent;
use App\Services\CallbackFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\DataProvider\CallbackFactory\CreateFromCompilationFailedEventDataProviderTrait;
use App\Tests\DataProvider\CallbackFactory\CreateFromJobTimeoutEventDataProviderTrait;
use App\Tests\DataProvider\CallbackFactory\CreateFromTestEventDataProviderTrait;
use App\Tests\Mock\Entity\MockCallback;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CallbackFactoryTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;
    use CreateFromCompilationFailedEventDataProviderTrait;
    use CreateFromTestEventDataProviderTrait;
    use CreateFromJobTimeoutEventDataProviderTrait;

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
     * @dataProvider createFromTestEventEventDataProvider
     * @dataProvider createFromJobTimeoutEventDataProvider
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
        return [
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
