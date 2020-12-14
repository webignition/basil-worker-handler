<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\JobReadyEventSubscriber;
use Symfony\Contracts\EventDispatcher\Event;

class JobReadyEventGetter
{
    public static function get(): InvokableInterface
    {
        return new Invokable(
            function (JobReadyEventSubscriber $jobReadyEventSubscriber): ?Event {
                return $jobReadyEventSubscriber->getEvent();
            },
            [
                new ServiceReference(JobReadyEventSubscriber::class),
            ]
        );
    }
}
