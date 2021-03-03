<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use webignition\BasilWorker\StateBundle\Services\ExecutionState;

class ExecutionStateGetterFactory
{
    public static function get(): InvokableInterface
    {
        return new Invokable(
            function (ExecutionState $executionState): string {
                return (string) $executionState;
            },
            [
                new ServiceReference(ExecutionState::class),
            ]
        );
    }
}
