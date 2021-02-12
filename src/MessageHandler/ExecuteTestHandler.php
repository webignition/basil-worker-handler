<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Event\TestExecuteCompleteEvent;
use App\Message\ExecuteTestMessage;
use App\Services\TestExecutor;
use App\Services\TestStateMutator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\EntityPersister;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\TestRepository;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;
use webignition\BasilWorker\StateBundle\Services\ExecutionState;

class ExecuteTestHandler implements MessageHandlerInterface
{
    private JobStore $jobStore;
    private EntityPersister $entityPersister;
    private TestExecutor $testExecutor;
    private EventDispatcherInterface $eventDispatcher;
    private TestStateMutator $testStateMutator;
    private TestRepository $testRepository;
    private ExecutionState $executionState;

    public function __construct(
        JobStore $jobStore,
        EntityPersister $entityPersister,
        TestExecutor $testExecutor,
        EventDispatcherInterface $eventDispatcher,
        TestStateMutator $testStateMutator,
        TestRepository $testRepository,
        ExecutionState $executionState
    ) {
        $this->jobStore = $jobStore;
        $this->entityPersister = $entityPersister;
        $this->testExecutor = $testExecutor;
        $this->eventDispatcher = $eventDispatcher;
        $this->testStateMutator = $testStateMutator;
        $this->testRepository = $testRepository;
        $this->executionState = $executionState;
    }

    public function __invoke(ExecuteTestMessage $message): void
    {
        if (false === $this->jobStore->has()) {
            return;
        }

        if (in_array($this->executionState->get(), ExecutionState::FINISHED_STATES)) {
            return;
        }

        $test = $this->testRepository->find($message->getTestId());
        if (null === $test) {
            return;
        }

        if (Test::STATE_AWAITING !== $test->getState()) {
            return;
        }

        $job = $this->jobStore->get();
        if (false === $job->hasStarted()) {
            $job->setStartDateTime();
            $this->entityPersister->persist($job);
        }

        $this->testStateMutator->setRunning($test);
        $this->testExecutor->execute($test);
        $this->testStateMutator->setComplete($test);

        $this->eventDispatcher->dispatch(new TestExecuteCompleteEvent($test));
    }
}
