<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class CallbackSetup
{
    /**
     * @var CallbackInterface::TYPE_*
     */
    private string $type;

    /**
     * @var array<mixed>
     */
    private array $payload;

    /**
     * @var CallbackInterface::STATE_*
     */
    private string $state;

    public function __construct()
    {
        $this->type = CallbackInterface::TYPE_COMPILATION_FAILED;
        $this->payload = [];
        $this->state = CallbackInterface::STATE_AWAITING;
    }

    /**
     * @return CallbackInterface::TYPE_*
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @return CallbackInterface::STATE_*
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param CallbackInterface::STATE_* $state
     *
     * @return $this
     */
    public function withState(string $state): self
    {
        $new = clone $this;
        $new->state = $state;

        return $new;
    }

    /**
     * @param CallbackInterface::TYPE_* $type
     * @return $this
     */
    public function withType(string $type): self
    {
        $new = clone $this;
        $new->type = $type;

        return $new;
    }
}
