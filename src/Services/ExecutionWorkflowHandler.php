<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\SourceCompilation\SourceCompilationPassedEvent;
use App\Event\TestExecuteCompleteEvent;
use App\Message\ExecuteTestMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\TestRepository;
use webignition\BasilWorker\StateBundle\Services\CompilationState;
use webignition\BasilWorker\StateBundle\Services\ExecutionState;

class ExecutionWorkflowHandler implements EventSubscriberInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private TestRepository $testRepository,
        private CompilationState $compilationState,
        private ExecutionState $executionState
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            SourceCompilationPassedEvent::class => [
                ['dispatchNextExecuteTestMessage', 0],
            ],
            TestExecuteCompleteEvent::class => [
                ['dispatchNextExecuteTestMessageFromTestExecuteCompleteEvent', 0],
            ],
        ];
    }

    public function dispatchNextExecuteTestMessageFromTestExecuteCompleteEvent(TestExecuteCompleteEvent $event): void
    {
        $test = $event->getTest();

        if (Test::STATE_COMPLETE === $test->getState()) {
            $this->dispatchNextExecuteTestMessage();
        }
    }

    public function dispatchNextExecuteTestMessage(): void
    {
        if (false === in_array($this->compilationState, CompilationState::FINISHED_STATES)) {
            return;
        }

        if (in_array($this->executionState, ExecutionState::FINISHED_STATES)) {
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
}
