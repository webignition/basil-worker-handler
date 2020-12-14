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

    public function testGetType()
    {
        self::assertSame(TimeoutCheckMessage::TYPE, $this->message->getType());
    }

    public function testGetPayload()
    {
        self::assertSame(
            [],
            $this->message->getPayload()
        );
    }

    public function testJsonSerialize()
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
