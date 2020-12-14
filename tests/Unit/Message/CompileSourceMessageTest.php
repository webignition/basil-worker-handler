<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use App\Message\CompileSourceMessage;
use PHPUnit\Framework\TestCase;

class CompileSourceMessageTest extends TestCase
{
    private const PATH = 'Test/test.yml';

    private CompileSourceMessage $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->message = new CompileSourceMessage(self::PATH);
    }

    public function testGetPath()
    {
        self::assertSame(self::PATH, $this->message->getPath());
    }

    public function testGetType()
    {
        self::assertSame(CompileSourceMessage::TYPE, $this->message->getType());
    }

    public function testGetPayload()
    {
        self::assertSame(
            [
                'path' => $this->message->getPath(),
            ],
            $this->message->getPayload()
        );
    }

    public function testJsonSerialize()
    {
        self::assertSame(
            [
                'type' => CompileSourceMessage::TYPE,
                'payload' => [
                    'path' => $this->message->getPath(),
                ],
            ],
            $this->message->jsonSerialize()
        );
    }
}
