<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SendCallbackMessage;
use App\Services\CallbackSender;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\CallbackStateMutator;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\CallbackRepository;

class SendCallbackHandler implements MessageHandlerInterface
{
    private CallbackRepository $repository;
    private CallbackSender $sender;
    private CallbackStateMutator $stateMutator;

    public function __construct(
        CallbackRepository $repository,
        CallbackSender $sender,
        CallbackStateMutator $stateMutator
    ) {
        $this->repository = $repository;
        $this->sender = $sender;
        $this->stateMutator = $stateMutator;
    }

    public function __invoke(SendCallbackMessage $message): void
    {
        $callback = $this->repository->find($message->getCallbackId());

        if ($callback instanceof CallbackInterface) {
            $this->stateMutator->setSending($callback);
            $this->sender->send($callback);
        }
    }
}
