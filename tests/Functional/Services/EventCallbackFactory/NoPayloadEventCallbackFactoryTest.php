<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\EventCallbackFactory;

use App\Services\EventCallbackFactory\EventCallbackFactoryInterface;
use App\Services\EventCallbackFactory\NoPayloadEventCallbackFactory;
use App\Tests\DataProvider\CallbackFactory\CreateFromCompilationCompletedEventDataProviderTrait;
use App\Tests\DataProvider\CallbackFactory\CreateFromExecutionCompletedEventDataProviderTrait;
use App\Tests\DataProvider\CallbackFactory\CreateFromExecutionStartedEventDataProviderTrait;
use App\Tests\DataProvider\CallbackFactory\CreateFromJobCompletedEventDataProviderTrait;
use App\Tests\DataProvider\CallbackFactory\CreateFromJobFailedEventDataProviderTrait;
use App\Tests\DataProvider\CallbackFactory\CreateFromJobReadyEventDataProviderTrait;

class NoPayloadEventCallbackFactoryTest extends AbstractEventCallbackFactoryTest
{
    use CreateFromJobReadyEventDataProviderTrait;
    use CreateFromCompilationCompletedEventDataProviderTrait;
    use CreateFromExecutionStartedEventDataProviderTrait;
    use CreateFromExecutionCompletedEventDataProviderTrait;
    use CreateFromJobCompletedEventDataProviderTrait;
    use CreateFromJobFailedEventDataProviderTrait;

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
            $this->createFromExecutionStartedEventDataProvider(),
            $this->createFromJobReadyEventDataProvider(),
            $this->createFromJobCompletedEventDataProvider(),
            $this->createFromExecutionCompletedEventDataProvider(),
            $this->createFromJobFailedEventDataProvider(),
        );
    }
}
