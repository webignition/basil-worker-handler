<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\CallbackFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\DataProvider\CallbackFactory\CreateFromCompilationFailedEventDataProviderTrait;
use App\Tests\DataProvider\CallbackFactory\CreateFromJobCompletedEventDataProviderTrait;
use App\Tests\DataProvider\CallbackFactory\CreateFromJobReadyEventDataProviderTrait;
use App\Tests\DataProvider\CallbackFactory\CreateFromJobTimeoutEventDataProviderTrait;
use App\Tests\DataProvider\CallbackFactory\CreateFromTestEventDataProviderTrait;
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
    use CreateFromJobCompletedEventDataProviderTrait;
    use CreateFromJobReadyEventDataProviderTrait;

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
     * @dataProvider createFromCompilationFailedEventDataProvider
     * @dataProvider createFromTestEventEventDataProvider
     * @dataProvider createFromJobTimeoutEventDataProvider
     * @dataProvider createFromJobCompletedEventDataProvider
     * @dataProvider createFromJobReadyEventDataProvider
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
}
