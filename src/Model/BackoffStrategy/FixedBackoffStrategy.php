<?php

declare(strict_types=1);

namespace App\Model\BackoffStrategy;

class FixedBackoffStrategy implements BackoffStrategyInterface
{
    public function __construct(private int $window)
    {
    }

    public function getDelay(int $retryCount): int
    {
        return $this->window;
    }
}
