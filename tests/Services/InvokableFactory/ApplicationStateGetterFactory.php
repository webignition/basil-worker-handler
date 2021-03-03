<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use webignition\BasilWorker\StateBundle\Services\ApplicationState;

class ApplicationStateGetterFactory
{
    public static function get(): InvokableInterface
    {
        return new Invokable(
            function (ApplicationState $applicationState): string {
                return (string) $applicationState;
            },
            [
                new ServiceReference(ApplicationState::class),
            ]
        );
    }
}
