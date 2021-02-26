<?php

declare(strict_types=1);

namespace App\Event;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class CallbackHttpErrorEvent extends Event
{
    public function __construct(
        private CallbackInterface $callback,
        private ClientExceptionInterface | ResponseInterface $context
    ) {
    }

    public function getCallback(): CallbackInterface
    {
        return $this->callback;
    }

    public function getContext(): ClientExceptionInterface | ResponseInterface
    {
        return $this->context;
    }
}
