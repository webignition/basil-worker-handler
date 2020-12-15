<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\Integration\HttpLogReader;

class HttpLogResetter
{
    public static function reset(): InvokableInterface
    {
        return new Invokable(
            function (HttpLogReader $httpLogReader): void {
                $httpLogReader->reset();
            },
            [
                new ServiceReference(HttpLogReader::class),
            ]
        );
    }
}
