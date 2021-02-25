<?php

declare(strict_types=1);

namespace App\Model\Callback;

use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class JobCompleteCallback extends AbstractCallbackWrapper
{
    public function __construct()
    {
        parent::__construct(CallbackEntity::create(
            CallbackInterface::TYPE_JOB_COMPLETE,
            []
        ));
    }
}
