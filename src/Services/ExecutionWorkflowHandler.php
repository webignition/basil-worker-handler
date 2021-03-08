<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\CompilationCompletedEvent;
use App\Event\ExecutionCompletedEvent;
use App\Event\ExecutionStartedEvent;
use App\Event\TestPassedEvent;
use App\Message\ExecuteTestMessage;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\CallbackRepository;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\TestRepository;
use webignition\BasilWorker\StateBundle\Services\CompilationState;
use webignition\BasilWorker\StateBundle\Services\ExecutionState;

class ExecutionWorkflowHandler implements EventSubscriberInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private TestRepository $testRepository,
        private CompilationState $compilationState,
        private ExecutionState $executionState,
        private CallbackRepository $callbackRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            TestPassedEvent::class => [
                ['dispatchNextExecuteTestMessageFromTestPassedEvent', 0],
                ['dispatchExecutionCompletedEvent', 10],
            ],
            CompilationCompletedEvent::class => [
                ['dispatchNextExecuteTestMessage', 0],
                ['dispatchExecutionStartedEvent', 50],
            ],
        ];
    }

    public function dispatchNextExecuteTestMessageFromTestPassedEvent(TestPassedEvent $event): void
    {
        $test = $event->getTest();

        if ($test->hasState(Test::STATE_COMPLETE)) {
            $this->dispatchNextExecuteTestMessage();
        }
    }

    public function dispatchNextExecuteTestMessage(): void
    {
        $testId = $this->testRepository->findNextAwaitingId();

        if (is_int($testId)) {
            $this->messageBus->dispatch(new ExecuteTestMessage($testId));
        }
    }

    public function dispatchExecutionStartedEvent(): void
    {
        $this->eventDispatcher->dispatch(new ExecutionStartedEvent());
    }

    public function dispatchExecutionCompletedEvent(TestPassedEvent $event): void
    {
        if (
            true === $this->executionState->is(ExecutionState::STATE_COMPLETE) &&
            false === $this->callbackRepository->hasForType(CallbackInterface::TYPE_EXECUTION_COMPLETED)
        ) {
            $this->eventDispatcher->dispatch(new ExecutionCompletedEvent());
        }
    }
}
