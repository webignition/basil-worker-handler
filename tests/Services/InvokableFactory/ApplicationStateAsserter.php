<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Services\ApplicationState;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use PHPUnit\Framework\TestCase;

class ApplicationStateAsserter
{
    public static function is(string $expectedState): InvokableInterface
    {
        return new Invokable(
            function (ApplicationState $applicationState, string $expectedState): void {
                TestCase::assertSame($expectedState, $applicationState->getCurrentState());
            },
            [
                new ServiceReference(ApplicationState::class),
                $expectedState,
            ]
        );
    }
}
