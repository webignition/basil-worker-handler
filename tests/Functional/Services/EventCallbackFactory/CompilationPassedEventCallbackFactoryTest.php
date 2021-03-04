<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\EventCallbackFactory;

use App\Services\EventCallbackFactory\CompilationPassedEventCallbackFactory;
use App\Services\EventCallbackFactory\EventCallbackFactoryInterface;
use App\Tests\DataProvider\CallbackFactory\CreateFromCompilationPassedEventDataProviderTrait;

class CompilationPassedEventCallbackFactoryTest extends AbstractEventCallbackFactoryTest
{
    use CreateFromCompilationPassedEventDataProviderTrait;

    protected function getCallbackFactory(): ?EventCallbackFactoryInterface
    {
        $callbackFactory = self::$container->get(CompilationPassedEventCallbackFactory::class);

        return $callbackFactory instanceof CompilationPassedEventCallbackFactory
            ? $callbackFactory
            : null;
    }

    public function createDataProvider(): array
    {
        return $this->createFromCompilationPassedEventDataProvider();
    }
}
