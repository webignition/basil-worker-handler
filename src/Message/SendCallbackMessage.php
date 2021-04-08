<?php

declare(strict_types=1);

namespace App\Message;

use webignition\JsonMessageSerializerBundle\Message\AbstractSerializableMessage;

class SendCallbackMessage extends AbstractSerializableMessage
{
    public const TYPE = 'send-callback';
    public const PAYLOAD_KEY_CALLBACK_ID = 'callback_id';

    public function __construct(private int $callbackId)
    {
    }

    /**
     * @param array<mixed> $data
     *
     * @return self
     */
    public static function createFromArray(array $data): self
    {
        return new SendCallbackMessage((int) ($data[self::PAYLOAD_KEY_CALLBACK_ID] ?? 0));
    }

    public function getCallbackId(): int
    {
        return $this->callbackId;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getPayload(): array
    {
        return [
            self::PAYLOAD_KEY_CALLBACK_ID => $this->callbackId,
        ];
    }
}
