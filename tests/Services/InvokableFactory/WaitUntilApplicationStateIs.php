<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\EntityRefresher;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\StateBundle\Services\ApplicationState;

class WaitUntilApplicationStateIs
{
    private const MAX_DURATION_IN_SECONDS = 30;
    private const MICROSECONDS_PER_SECOND = 1000000;

    /**
     * @param array<ApplicationState::STATE_*> $expectedEndStates
     */
    public static function create(array $expectedEndStates): InvokableInterface
    {
        return new Invokable(
            function (
                ApplicationState $applicationState,
                EntityRefresher $entityRefresher,
                array $expectedEndStates
            ): bool {
                $duration = 0;
                $maxDuration = self::MAX_DURATION_IN_SECONDS * self::MICROSECONDS_PER_SECOND;
                $maxDurationReached = false;
                $intervalInMicroseconds = 100000;

                while (
                    false === in_array($applicationState->get(), $expectedEndStates) &&
                    false === $maxDurationReached
                ) {
                    usleep($intervalInMicroseconds);
                    $duration += $intervalInMicroseconds;
                    $maxDurationReached = $duration >= $maxDuration;

                    if ($maxDurationReached) {
                        return false;
                    }

                    $entityRefresher->refreshForEntities([
                        Job::class,
                        Test::class,
                        CallbackEntity::class,
                    ]);
                }

                return true;
            },
            [
                new ServiceReference(ApplicationState::class),
                new ServiceReference(EntityRefresher::class),
                $expectedEndStates,
            ]
        );
    }
}
