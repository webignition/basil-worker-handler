<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\BackoffStrategy;

use App\Model\BackoffStrategy\ExponentialBackoffStrategy;
use PHPUnit\Framework\TestCase;

class ExponentialBackoffStrategyTest extends TestCase
{
    /**
     * @dataProvider getDelayDataProvider
     */
    public function testGetDelay(ExponentialBackoffStrategy $backoffStrategy, int $retryCount, int $expectedDelay): void
    {
        self::assertSame($expectedDelay, $backoffStrategy->getDelay($retryCount));
    }

    /**
     * @return array[]
     */
    public function getDelayDataProvider(): array
    {
        return [
            'window 500, retryCount 0' => [
                'backoffStrategy' => new ExponentialBackoffStrategy(500),
                'retryCount' => 0,
                'expectedDelay' => 0,
            ],
            'window 500, retryCount 1' => [
                'backoffStrategy' => new ExponentialBackoffStrategy(500),
                'retryCount' => 1,
                'expectedDelay' => 500,
            ],
            'window 500, retryCount 2' => [
                'backoffStrategy' => new ExponentialBackoffStrategy(500),
                'retryCount' => 2,
                'expectedDelay' => 1500,
            ],
            'window 500, retryCount 3' => [
                'backoffStrategy' => new ExponentialBackoffStrategy(500),
                'retryCount' => 3,
                'expectedDelay' => 3500,
            ],
            'window 1000, retryCount 0' => [
                'backoffStrategy' => new ExponentialBackoffStrategy(1000),
                'retryCount' => 0,
                'expectedDelay' => 0,
            ],
            'window 1000, retryCount 1' => [
                'backoffStrategy' => new ExponentialBackoffStrategy(1000),
                'retryCount' => 1,
                'expectedDelay' => 1000,
            ],
            'window 1000, retryCount 2' => [
                'backoffStrategy' => new ExponentialBackoffStrategy(1000),
                'retryCount' => 2,
                'expectedDelay' => 3000,
            ],
            'window 1000, retryCount 3' => [
                'backoffStrategy' => new ExponentialBackoffStrategy(1000),
                'retryCount' => 3,
                'expectedDelay' => 7000,
            ],
        ];
    }
}
