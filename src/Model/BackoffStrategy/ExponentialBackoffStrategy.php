<?php

declare(strict_types=1);

namespace App\Model\BackoffStrategy;

class ExponentialBackoffStrategy implements BackoffStrategyInterface
{
    public function __construct(private int $window = 1000)
    {
    }

    public function getDelay(int $retryCount): int
    {
        $factor = (2 ** $retryCount) - 1;

        return $factor * $this->window;
    }
}
