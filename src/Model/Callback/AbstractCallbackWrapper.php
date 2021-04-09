<?php

declare(strict_types=1);

namespace App\Model\Callback;

use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

abstract class AbstractCallbackWrapper implements CallbackInterface
{
    public function __construct(private CallbackInterface $callback)
    {
    }

    public function getEntity(): CallbackEntity
    {
        return $this->callback->getEntity();
    }

    public function getId(): ?int
    {
        return $this->callback->getId();
    }

    public function getState(): string
    {
        return $this->callback->getState();
    }

    public function hasState(string $state): bool
    {
        return $this->callback->hasState($state);
    }

    public function setState(string $state): void
    {
        $this->callback->setState($state);
    }

    public function getRetryCount(): int
    {
        return $this->callback->getRetryCount();
    }

    public function getType(): string
    {
        return $this->callback->getType();
    }

    public function getPayload(): array
    {
        return $this->callback->getPayload();
    }

    public function incrementRetryCount(): void
    {
        $this->callback->incrementRetryCount();
    }
}
