<?php

declare(strict_types=1);

namespace App\Message;

use webignition\JsonMessageSerializerBundle\Message\AbstractSerializableMessage;

class CompileSourceMessage extends AbstractSerializableMessage
{
    public const TYPE = 'compile-source';
    public const PAYLOAD_KEY_PATH = 'path';

    public function __construct(private string $path)
    {
    }

    /**
     * @param array<mixed> $data
     *
     * @return self
     */
    public static function createFromArray(array $data): self
    {
        return new CompileSourceMessage((string) ($data[self::PAYLOAD_KEY_PATH] ?? ''));
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getPayload(): array
    {
        return [
            self::PAYLOAD_KEY_PATH => $this->path,
        ];
    }
}
