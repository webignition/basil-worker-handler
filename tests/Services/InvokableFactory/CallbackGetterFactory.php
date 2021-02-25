<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\CallbackRepository;

class CallbackGetterFactory
{
    public static function getAll(): InvokableInterface
    {
        return new Invokable(
            function (CallbackRepository $callbackRepository): array {
                return $callbackRepository->findAll();
            },
            [
                new ServiceReference(CallbackRepository::class),
            ]
        );
    }
}
