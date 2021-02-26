<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class CallbackEvent extends Event implements CallbackEventInterface
{
    public function __construct(private CallbackInterface $callback)
    {
    }

    public function getCallback(): CallbackInterface
    {
        return $this->callback;
    }
}
