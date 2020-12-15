<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Services\ExecutionState;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use PHPUnit\Framework\TestCase;

class ExecutionStateAsserter
{
    /**
     * @param ExecutionState::STATE_* $expectedState
     */
    public static function is(string $expectedState): InvokableInterface
    {
        return new Invokable(
            function (ExecutionState $executionState, string $expectedState): void {
                TestCase::assertSame($expectedState, $executionState->getCurrentState());
            },
            [
                new ServiceReference(ExecutionState::class),
                $expectedState,
            ]
        );
    }
}
