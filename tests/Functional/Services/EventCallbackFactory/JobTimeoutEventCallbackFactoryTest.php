<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\EventCallbackFactory;

use App\Services\EventCallbackFactory\EventCallbackFactoryInterface;
use App\Services\EventCallbackFactory\JobTimeoutEventCallbackFactory;
use App\Tests\DataProvider\CallbackFactory\CreateFromJobTimeoutEventDataProviderTrait;

class JobTimeoutEventCallbackFactoryTest extends AbstractEventCallbackFactoryTest
{
    use CreateFromJobTimeoutEventDataProviderTrait;

    protected function getCallbackFactory(): ?EventCallbackFactoryInterface
    {
        $callbackFactory = self::$container->get(JobTimeoutEventCallbackFactory::class);

        return $callbackFactory instanceof JobTimeoutEventCallbackFactory
            ? $callbackFactory
            : null;
    }

    public function createDataProvider(): array
    {
        return $this->createFromJobTimeoutEventDataProvider();
    }
}
