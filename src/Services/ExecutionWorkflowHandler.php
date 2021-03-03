<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\ExecutionStartedEvent;
use App\Event\SourceCompilation\SourceCompilationPassedEvent;
use App\Event\TestExecuteCompleteEvent;
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
            SourceCompilationPassedEvent::class => [
                ['dispatchNextExecuteTestMessage', 0],
                ['dispatchExecutionStartedEvent', 50],
            ],
            TestExecuteCompleteEvent::class => [
                ['dispatchNextExecuteTestMessageFromTestExecuteCompleteEvent', 0],
            ],
        ];
    }

    public function dispatchNextExecuteTestMessageFromTestExecuteCompleteEvent(TestExecuteCompleteEvent $event): void
    {
        $test = $event->getTest();

        if ($test->hasState(Test::STATE_COMPLETE)) {
            $this->dispatchNextExecuteTestMessage();
        }
    }

    public function dispatchNextExecuteTestMessage(): void
    {
        if (false === $this->compilationState->is(...CompilationState::FINISHED_STATES)) {
            return;
        }

        if ($this->executionState->is(...ExecutionState::FINISHED_STATES)) {
            return;
        }

        $nextAwaitingTest = $this->testRepository->findNextAwaiting();

        if ($nextAwaitingTest instanceof Test) {
            $testId = $nextAwaitingTest->getId();

            if (is_int($testId)) {
                $message = new ExecuteTestMessage($testId);
                $this->messageBus->dispatch($message);
            }
        }
    }

    public function dispatchExecutionStartedEvent(): void
    {
        if (
            ExecutionState::STATE_AWAITING === (string) $this->executionState &&
            false == $this->callbackRepository->hasForType(CallbackInterface::TYPE_EXECUTION_STARTED)
        ) {
            $this->eventDispatcher->dispatch(new ExecutionStartedEvent());
        }
    }
}
