<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\EventCallbackFactory;

use App\Services\EventCallbackFactory\EventCallbackFactoryInterface;
use App\Services\EventCallbackFactory\NoPayloadEventCallbackFactory;
use App\Tests\DataProvider\CallbackFactory\CreateFromJobCompletedEventDataProviderTrait;

class NoPayloadEventCallbackFactoryTest extends AbstractEventCallbackFactoryTest
{
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
            $this->createFromJobCompletedEventDataProvider(),
        );
    }
}
