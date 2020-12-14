<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Event\TestExecuteCompleteEvent;
use App\Message\ExecuteTestMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\TestRepository;

class ExecutionWorkflowHandler implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private TestRepository $testRepository;
    private CompilationState $compilationState;
    private ExecutionState $executionState;

    public function __construct(
        MessageBusInterface $messageBus,
        TestRepository $testRepository,
        CompilationState $compilationState,
        ExecutionState $executionState
    ) {
        $this->messageBus = $messageBus;
        $this->testRepository = $testRepository;
        $this->compilationState = $compilationState;
        $this->executionState = $executionState;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourceCompileSuccessEvent::class => [
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
}
