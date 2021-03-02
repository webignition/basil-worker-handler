<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

interface EventCallbackFactoryInterface
{
    public function handles(Event $event): bool;
    public function createForEvent(Event $event): ?CallbackInterface;
}
