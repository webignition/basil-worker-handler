<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Model\BackoffStrategy\ExponentialBackoffStrategy;

class MaximumNumberOfDelaysExponentialBackoffStrategy extends ExponentialBackoffStrategy
{
    private int $maximumNumberOfDelays;

    public function __construct(int $maximumNumberOfDelays, int $window = 1000)
    {
        parent::__construct($window);

        $this->maximumNumberOfDelays = $maximumNumberOfDelays;
    }

    public function getDelay(int $retryCount): int
    {
        if ($retryCount < $this->maximumNumberOfDelays) {
            return parent::getDelay($retryCount);
        }

        return 0;
    }
}
