<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\EventCallbackFactory;

use App\Services\EventCallbackFactory\CompilationFailedEventCallbackFactory;
use App\Services\EventCallbackFactory\EventCallbackFactoryInterface;
use App\Tests\DataProvider\CallbackFactory\CreateFromCompilationFailedEventDataProviderTrait;

class CompilationFailedEventCallbackFactoryTest extends AbstractEventCallbackFactoryTest
{
    use CreateFromCompilationFailedEventDataProviderTrait;

    protected function getCallbackFactory(): ?EventCallbackFactoryInterface
    {
        $callbackFactory = self::$container->get(CompilationFailedEventCallbackFactory::class);

        return $callbackFactory instanceof CompilationFailedEventCallbackFactory
            ? $callbackFactory
            : null;
    }

    public function createDataProvider(): array
    {
        return $this->createFromCompilationFailedEventDataProvider();
    }
}
