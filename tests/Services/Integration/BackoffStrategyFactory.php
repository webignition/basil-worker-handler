<?php

declare(strict_types=1);

namespace App\Tests\Services\Integration;

use App\Model\BackoffStrategy\BackoffStrategyInterface;
use App\Services\BackoffStrategyFactory as ServiceBackoffStrategyFactory;
use App\Tests\Model\MaximumNumberOfDelaysExponentialBackoffStrategy;

class BackoffStrategyFactory extends ServiceBackoffStrategyFactory
{
    private int $maximumNumberOfDelays;

    public function __construct(int $maximumNumberOfDelays)
    {
        $this->maximumNumberOfDelays = $maximumNumberOfDelays;
    }

    public function create(object $context): BackoffStrategyInterface
    {
        return new MaximumNumberOfDelaysExponentialBackoffStrategy($this->maximumNumberOfDelays);
    }
}
