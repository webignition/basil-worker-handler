<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use App\Message\SendCallbackMessage;
use PHPUnit\Framework\TestCase;

class SendCallbackMessageTest extends TestCase
{
    private const CALLBACK_ID = 9;

    private SendCallbackMessage $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->message = new SendCallbackMessage(self::CALLBACK_ID);
    }

    public function testGetCallbackId()
    {
        self::assertSame(self::CALLBACK_ID, $this->message->getCallbackId());
    }

    public function testGetType()
    {
        self::assertSame(SendCallbackMessage::TYPE, $this->message->getType());
    }

    public function testGetPayload()
    {
        self::assertSame(
            [
                'callback_id' => $this->message->getCallbackId(),
            ],
            $this->message->getPayload()
        );
    }

    public function testJsonSerialize()
    {
        self::assertSame(
            [
                'type' => SendCallbackMessage::TYPE,
                'payload' => [
                    'callback_id' => $this->message->getCallbackId(),
                ],
            ],
            $this->message->jsonSerialize()
        );
    }
}
