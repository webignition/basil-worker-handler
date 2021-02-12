<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use PHPUnit\Framework\TestCase;
use webignition\BasilWorker\StateBundle\Services\ApplicationState;

class ApplicationStateAsserter
{
    public static function is(string $expectedState): InvokableInterface
    {
        return new Invokable(
            function (ApplicationState $applicationState, string $expectedState): void {
                TestCase::assertSame($expectedState, $applicationState->get());
            },
            [
                new ServiceReference(ApplicationState::class),
                $expectedState,
            ]
        );
    }
}
