<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\EventCallbackFactory;

use App\Services\EventCallbackFactory\EventCallbackFactoryInterface;
use App\Services\EventCallbackFactory\NoPayloadEventCallbackFactory;
use App\Tests\DataProvider\CallbackFactory\CreateFromCompilationCompletedEventDataProviderTrait;
use App\Tests\DataProvider\CallbackFactory\CreateFromJobCompletedEventDataProviderTrait;
use App\Tests\DataProvider\CallbackFactory\CreateFromJobReadyEventDataProviderTrait;

class NoPayloadEventCallbackFactoryTest extends AbstractEventCallbackFactoryTest
{
    use CreateFromJobReadyEventDataProviderTrait;
    use CreateFromCompilationCompletedEventDataProviderTrait;
    use CreateFromJobCompletedEventDataProviderTrait;

    protected function getCallbackFactory(): ?EventCallbackFactoryInterface
    {
        $callbackFactory = self::$container->get(NoPayloadEventCallbackFactory::class);

        return $callbackFactory instanceof NoPayloadEventCallbackFactory
            ? $callbackFactory
            : null;
    }

    public function createDataProvider(): array
    {
        return array_merge(
            $this->createFromCompilationCompletedEventDataProvider(),
            $this->createFromJobReadyEventDataProvider(),
            $this->createFromJobCompletedEventDataProvider(),
        );
    }
}
