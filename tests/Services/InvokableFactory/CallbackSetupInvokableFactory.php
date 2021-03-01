<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\InvokableItemInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\EntityPersister;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\CallbackFactory;

class CallbackSetupInvokableFactory
{
    public static function setup(?CallbackSetup $callbackSetup = null): InvokableInterface
    {
        $callbackSetup = $callbackSetup instanceof CallbackSetup ? $callbackSetup : new CallbackSetup();

        $collection = [];

        $collection[] = self::setState(
            self::create($callbackSetup->getType(), $callbackSetup->getPayload()),
            $callbackSetup->getState()
        );

        return new InvokableCollection($collection);
    }

    /**
     * @param CallbackInterface::TYPE_* $type
     * @param array<mixed> $payload
     *
     * @return InvokableItemInterface
     */
    private static function create(string $type, array $payload): InvokableItemInterface
    {
        return new Invokable(
            function (CallbackFactory $callbackFactory, string $type, array $payload): CallbackInterface {
                if (
                    CallbackInterface::TYPE_COMPILATION_FAILED !== $type &&
                    CallbackInterface::TYPE_STEP_PASSED !== $type &&
                    CallbackInterface::TYPE_JOB_TIME_OUT !== $type
                ) {
                    $type = CallbackInterface::TYPE_COMPILATION_FAILED;
                }

                return $callbackFactory->create($type, $payload);
            },
            [
                new ServiceReference(CallbackFactory::class),
                $type,
                $payload,
            ]
        );
    }

    /**
     * @param CallbackInterface::STATE_* $state
     *
     * @return InvokableInterface
     */
    private static function setState(InvokableItemInterface $creator, string $state): InvokableInterface
    {
        return new Invokable(
            function (EntityPersister $entityPersister, CallbackInterface $callback, string $state): CallbackInterface {
                if (
                    CallbackInterface::STATE_AWAITING !== $state &&
                    CallbackInterface::STATE_QUEUED !== $state &&
                    CallbackInterface::STATE_SENDING !== $state &&
                    CallbackInterface::STATE_FAILED !== $state &&
                    CallbackInterface::STATE_COMPLETE !== $state
                ) {
                    $state = CallbackInterface::STATE_AWAITING;
                }

                $callback->setState($state);
                $entityPersister->persist($callback);

                return $callback;
            },
            [
                new ServiceReference(EntityPersister::class),
                $creator,
                $state
            ]
        );
    }
}
