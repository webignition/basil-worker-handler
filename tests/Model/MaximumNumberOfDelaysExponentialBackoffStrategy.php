<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Model\BackoffStrategy\ExponentialBackoffStrategy;

class MaximumNumberOfDelaysExponentialBackoffStrategy extends ExponentialBackoffStrategy
{
    public function __construct(
        private int $maximumNumberOfDelays,
        int $window = 1000
    ) {
        parent::__construct($window);
    }

    public function getDelay(int $retryCount): int
    {
        if ($retryCount < $this->maximumNumberOfDelays) {
            return parent::getDelay($retryCount);
        }

        return 0;
    }
}
