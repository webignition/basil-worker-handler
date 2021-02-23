<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\StampCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Component\Messenger\Stamp\ValidationStamp;

class StampCollectionTest extends TestCase
{
    /**
     * @dataProvider getStampsDataProvider
     *
     * @param StampInterface[] $expectedStamps
     */
    public function testGetStamps(StampCollection $collection, array $expectedStamps): void
    {
        self::assertSame($expectedStamps, $collection->getStamps());
    }

    /**
     * @return array[]
     */
    public function getStampsDataProvider(): array
    {
        $delayStamp = new DelayStamp(1000);
        $validationStamp = new ValidationStamp([]);

        return [
            'empty' => [
                'collection' => new StampCollection(),
                'expectedStamps' => [],
            ],
            'non-empty' => [
                'collection' => new StampCollection([
                    true,
                    'string',
                    100,
                    $delayStamp,
                    $validationStamp,
                ]),
                'expectedStamps' => [
                    $delayStamp,
                    $validationStamp,
                ],
            ],
        ];
    }

    /**
     * @dataProvider countDataProvider
     */
    public function testCount(StampCollection $collection, int $expectedCount): void
    {
        self::assertCount($expectedCount, $collection);
    }

    /**
     * @return array[]
     */
    public function countDataProvider(): array
    {
        return [
            'empty' => [
                'collection' => new StampCollection(),
                'expectedCount' => 0,
            ],
            'non-empty' => [
                'collection' => new StampCollection([
                    true,
                    'string',
                    100,
                    new DelayStamp(1000),
                    new ValidationStamp([]),
                ]),
                'expectedCount' => 2,
            ],
        ];
    }

    /**
     * @dataProvider hasStampsDataProvider
     */
    public function testHasStamps(StampCollection $collection, bool $expectedIsEmpty): void
    {
        self::assertSame($expectedIsEmpty, $collection->hasStamps());
    }

    /**
     * @return array[]
     */
    public function hasStampsDataProvider(): array
    {
        return [
            'empty' => [
                'collection' => new StampCollection(),
                'expectedHasStamps' => false,
            ],
            'non-empty' => [
                'collection' => new StampCollection([
                    true,
                    'string',
                    100,
                    new DelayStamp(1000),
                    new ValidationStamp([]),
                ]),
                'expectedHasStamps' => true,
            ],
        ];
    }
}
