<?php

declare(strict_types=1);

namespace App\Message;

use webignition\JsonMessageSerializerBundle\Message\AbstractSerializableMessage;

class JobCompletedCheckMessage extends AbstractSerializableMessage
{
    public const TYPE = 'job-complete-check';

    public static function createFromArray(array $data): self
    {
        return new JobCompletedCheckMessage();
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
