<?php

declare(strict_types=1);

namespace App\Model\Callback;

use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class JobTimeoutCallback extends AbstractCallbackWrapper
{
    public function __construct(private int $maximumDurationInSeconds)
    {
        parent::__construct(CallbackEntity::create(
            CallbackInterface::TYPE_JOB_TIMEOUT,
            [
                'maximum_duration_in_seconds' => $maximumDurationInSeconds,
            ]
        ));
    }

    public function getMaximumDurationInSeconds(): int
    {
        return $this->maximumDurationInSeconds;
    }
}
