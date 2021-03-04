<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Event\TestFailedEvent;
use App\Event\TestPassedEvent;
use App\Event\TestStartedEvent;
use App\Message\ExecuteTestMessage;
use App\Services\TestDocumentFactory;
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
    public function __construct(
        private JobStore $jobStore,
        private EntityPersister $entityPersister,
        private TestExecutor $testExecutor,
        private EventDispatcherInterface $eventDispatcher,
        private TestStateMutator $testStateMutator,
        private TestRepository $testRepository,
        private ExecutionState $executionState,
        private TestDocumentFactory $testDocumentFactory
    ) {
    }

    public function __invoke(ExecuteTestMessage $message): void
    {
        if (false === $this->jobStore->has()) {
            return;
        }

        if ($this->executionState->is(...ExecutionState::FINISHED_STATES)) {
            return;
        }

        $test = $this->testRepository->find($message->getTestId());
        if (null === $test) {
            return;
        }

        if (false === $test->hasState(Test::STATE_AWAITING)) {
            return;
        }

        $job = $this->jobStore->get();
        if (false === $job->hasStarted()) {
            $job->setStartDateTime();
            $this->entityPersister->persist($job);
        }

        $testDocument = $this->testDocumentFactory->create($test);

        $this->eventDispatcher->dispatch(new TestStartedEvent($test, $testDocument));

        $this->testStateMutator->setRunning($test);
        $this->testExecutor->execute($test);
        $this->testStateMutator->setCompleteIfRunning($test);

        if ($test->hasState(Test::STATE_COMPLETE)) {
            $this->eventDispatcher->dispatch(new TestPassedEvent($test, $testDocument));
        } else {
            $this->eventDispatcher->dispatch(new TestFailedEvent($test, $testDocument));
        }
    }
}
