<?php

declare(strict_types=1);

namespace App\Model\Callback;

use App\Model\BackoffStrategy\BackoffStrategyInterface;
use App\Model\StampCollection;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class DelayedCallback extends AbstractCallbackWrapper implements StampedCallbackInterface
{
    public function __construct(CallbackInterface $callback, private BackoffStrategyInterface $backoffStrategy)
    {
        parent::__construct($callback);
    }

    public function getStamps(): StampCollection
    {
        $delay = $this->backoffStrategy->getDelay($this->getRetryCount());
        $stamps = [];

        if (0 !== $delay) {
            $stamps[] = new DelayStamp($delay);
        }

        return new StampCollection($stamps);
    }
}
