<?php

declare(strict_types=1);

namespace App\Event;

use App\Model\Callback\JobCompleteCallback;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class JobCompleteEvent extends Event implements CallbackEventInterface
{
    public function __construct(private JobCompleteCallback $callback)
    {
    }

    public function getCallback(): CallbackInterface
    {
        return $this->callback;
    }
}
