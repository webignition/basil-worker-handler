<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use webignition\SymfonyMessengerMessageDispatcher\MessageDispatcher;

abstract class AbstractMessageDispatcher implements EventSubscriberInterface
{
    public function __construct(
        private MessageDispatcher $messageDispatcher,
    ) {
    }

    protected function doDispatch(object $message): Envelope
    {
        return $this->messageDispatcher->dispatch($message);
    }
}
