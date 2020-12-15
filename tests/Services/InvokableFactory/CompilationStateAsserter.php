<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Services\CompilationState;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use PHPUnit\Framework\TestCase;

class CompilationStateAsserter
{
    /**
     * @param CompilationState::STATE_* $expectedState
     */
    public static function is(string $expectedState): InvokableInterface
    {
        return new Invokable(
            function (CompilationState $compilationState, string $expectedState): void {
                TestCase::assertSame($expectedState, $compilationState->getCurrentState());
            },
            [
                new ServiceReference(CompilationState::class),
                $expectedState,
            ]
        );
    }
}
