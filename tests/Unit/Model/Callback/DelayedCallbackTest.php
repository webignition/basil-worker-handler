<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Callback;

use App\Model\BackoffStrategy\ExponentialBackoffStrategy;
use App\Model\Callback\DelayedCallback;
use App\Model\StampCollection;
use App\Tests\Model\TestCallback;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class DelayedCallbackTest extends TestCase
{
    /**
     * @dataProvider encapsulatesDataProvider
     */
    public function testEncapsulates(CallbackInterface $callback): void
    {
        $delayedCallback = new DelayedCallback($callback, new ExponentialBackoffStrategy());

        self::assertSame($callback->getRetryCount(), $delayedCallback->getRetryCount());
        self::assertSame($callback->getType(), $delayedCallback->getType());
        self::assertSame($callback->getPayload(), $delayedCallback->getPayload());
    }

    /**
     * @return array[]
     */
    public function encapsulatesDataProvider(): array
    {
        return [
            'no explicit data, retry count 0' => [
                'callback' => (new TestCallback())
                    ->withRetryCount(0),
            ],
            'no explicit data, retry count 1' => [
                'callback' => (new TestCallback())
                    ->withRetryCount(1),
            ],
            'with explicit data, retry count 1' => [
                'callback' => (new TestCallback())
                    ->withPayload(['key' => 'value'])
                    ->withRetryCount(1),
            ],
            'with type' => [
                'callback' => (new TestCallback())
                    ->withType(CallbackInterface::TYPE_JOB_COMPLETED),
            ],
        ];
    }

    /**
     * @dataProvider getStampsDataProvider
     */
    public function testGetStamps(DelayedCallback $callback, StampCollection $expectedStamps): void
    {
        self::assertEquals($expectedStamps, $callback->getStamps());
    }

    /**
     * @return array[]
     */
    public function getStampsDataProvider(): array
    {
        return [
            'retryCount 0' => [
                'callback' => new DelayedCallback(
                    (new TestCallback())
                        ->withRetryCount(0),
                    new ExponentialBackoffStrategy()
                ),
                'expectedStamps' => new StampCollection(),
            ],
            'retryCount 1' => [
                'callback' => new DelayedCallback(
                    (new TestCallback())
                        ->withRetryCount(1),
                    new ExponentialBackoffStrategy()
                ),
                'expectedStamps' => new StampCollection([
                    new DelayStamp(1000),
                ]),
            ],
            'retryCount 2' => [
                'callback' => new DelayedCallback(
                    (new TestCallback())
                        ->withRetryCount(2),
                    new ExponentialBackoffStrategy()
                ),
                'expectedStamps' => new StampCollection([
                    new DelayStamp(3000),
                ]),
            ],
            'retryCount 3' => [
                'callback' => new DelayedCallback(
                    (new TestCallback())
                        ->withRetryCount(3),
                    new ExponentialBackoffStrategy()
                ),
                'expectedStamps' => new StampCollection([
                    new DelayStamp(7000),
                ]),
            ],
        ];
    }
}
