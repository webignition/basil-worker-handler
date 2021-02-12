<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use webignition\BasilWorker\StateBundle\Services\CompilationState;

class CompilationStateGetterFactory
{
    public static function get(): InvokableInterface
    {
        return new Invokable(
            function (CompilationState $compilationState): string {
                return $compilationState->get();
            },
            [
                new ServiceReference(CompilationState::class),
            ]
        );
    }
}
