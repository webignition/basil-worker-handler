<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\CallbackHttpErrorEvent;
use App\Model\Callback\DelayedCallback;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class CallbackResponseHandler
{
    private EventDispatcherInterface $eventDispatcher;
    private BackoffStrategyFactory $backoffStrategyFactory;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        BackoffStrategyFactory $backoffStrategyFactory
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->backoffStrategyFactory = $backoffStrategyFactory;
    }

    public function handle(CallbackInterface $callback, ClientExceptionInterface | ResponseInterface $context): void
    {
        $callback->incrementRetryCount();
        $callback = $this->createNextCallback($callback, $context);

        $this->eventDispatcher->dispatch(new CallbackHttpErrorEvent($callback, $context));
    }

    private function createNextCallback(
        CallbackInterface $callback,
        ClientExceptionInterface | ResponseInterface $context
    ): CallbackInterface {
        if (0 === $callback->getRetryCount()) {
            return $callback;
        }

        return new DelayedCallback(
            $callback,
            $this->backoffStrategyFactory->create($context)
        );
    }
}
