<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Message\JobCompletedCheckMessage;

class JobCompletedCheckMessageDispatcher extends AbstractDelayedMessageDispatcher
{
    protected function createMessage(): object
    {
        return new JobCompletedCheckMessage();
    }
}
