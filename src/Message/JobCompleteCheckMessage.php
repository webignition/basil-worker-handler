<?php

declare(strict_types=1);

namespace App\Message;

use webignition\JsonMessageSerializerBundle\Message\AbstractSerializableMessage;

class JobCompleteCheckMessage extends AbstractSerializableMessage
{
    public const TYPE = 'job-complete-check';

    public static function createFromArray(array $data): self
    {
        return new JobCompleteCheckMessage();
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
