<?php

declare(strict_types=1);

namespace App\Tests\Services\Integration;

use App\Model\BackoffStrategy\BackoffStrategyInterface;
use App\Services\BackoffStrategyFactory as ServiceBackoffStrategyFactory;
use App\Tests\Model\MaximumNumberOfDelaysExponentialBackoffStrategy;

class BackoffStrategyFactory extends ServiceBackoffStrategyFactory
{
    public function __construct(private int $maximumNumberOfDelays)
    {
    }

    public function create(object $context): BackoffStrategyInterface
    {
        return new MaximumNumberOfDelaysExponentialBackoffStrategy($this->maximumNumberOfDelays);
    }
}
