<?php

declare(strict_types=1);

namespace App\Event;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class CallbackHttpErrorEvent extends Event implements CallbackEventInterface
{
    private CallbackInterface $callback;
    private ClientExceptionInterface | ResponseInterface $context;

    /**
     * @param CallbackInterface $callback
     * @param ClientExceptionInterface|ResponseInterface $context
     */
    public function __construct(CallbackInterface $callback, ClientExceptionInterface | ResponseInterface $context)
    {
        $this->callback = $callback;
        $this->context = $context;
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
