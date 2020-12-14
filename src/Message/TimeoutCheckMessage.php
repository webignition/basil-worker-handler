<?php

declare(strict_types=1);

namespace App\Message;

use webignition\JsonMessageSerializerBundle\Message\AbstractSerializableMessage;

class TimeoutCheckMessage extends AbstractSerializableMessage
{
    public const TYPE = 'timeout-check';

    public static function createFromArray(array $data): self
    {
        return new TimeoutCheckMessage();
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getPayload(): array
    {
        return [];
    }
}
