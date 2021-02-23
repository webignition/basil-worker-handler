<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use App\Message\TimeoutCheckMessage;
use PHPUnit\Framework\TestCase;

class TimeoutCheckMessageTest extends TestCase
{
    private TimeoutCheckMessage $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->message = new TimeoutCheckMessage();
    }

    public function testGetType(): void
    {
        self::assertSame(TimeoutCheckMessage::TYPE, $this->message->getType());
    }

    public function testGetPayload(): void
    {
        self::assertSame(
            [],
            $this->message->getPayload()
        );
    }

    public function testJsonSerialize(): void
    {
        self::assertSame(
            [
                'type' => TimeoutCheckMessage::TYPE,
                'payload' => [],
            ],
            $this->message->jsonSerialize()
        );
    }
}
