<?php

declare(strict_types=1);

namespace App\Event;

use App\Model\Callback\JobTimeoutCallback;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class JobTimeoutEvent extends Event implements CallbackEventInterface
{
    private CallbackInterface $callback;

    public function __construct(private int $jobMaximumDuration)
    {
        $this->callback = new JobTimeoutCallback($this->jobMaximumDuration);
    }

    public function getJobMaximumDuration(): int
    {
        return $this->jobMaximumDuration;
    }

    public function getCallback(): CallbackInterface
    {
        return $this->callback;
    }
}
