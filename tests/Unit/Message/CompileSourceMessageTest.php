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

    public function testGetPath(): void
    {
        self::assertSame(self::PATH, $this->message->getPath());
    }

    public function testGetType(): void
    {
        self::assertSame(CompileSourceMessage::TYPE, $this->message->getType());
    }

    public function testGetPayload(): void
    {
        self::assertSame(
            [
                'path' => $this->message->getPath(),
            ],
            $this->message->getPayload()
        );
    }

    public function testJsonSerialize(): void
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
