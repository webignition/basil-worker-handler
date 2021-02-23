<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use App\Message\ExecuteTestMessage;
use PHPUnit\Framework\TestCase;

class ExecuteTestMessageTest extends TestCase
{
    private const TEST_ID = 7;

    private ExecuteTestMessage $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->message = new ExecuteTestMessage(self::TEST_ID);
    }

    public function testGetTestId(): void
    {
        self::assertSame(self::TEST_ID, $this->message->getTestId());
    }

    public function testGetType(): void
    {
        self::assertSame(ExecuteTestMessage::TYPE, $this->message->getType());
    }

    public function testGetPayload(): void
    {
        self::assertSame(
            [
                'test_id' => $this->message->getTestId(),
            ],
            $this->message->getPayload()
        );
    }

    public function testJsonSerialize(): void
    {
        self::assertSame(
            [
                'type' => ExecuteTestMessage::TYPE,
                'payload' => [
                    'test_id' => $this->message->getTestId(),
                ],
            ],
            $this->message->jsonSerialize()
        );
    }
}
