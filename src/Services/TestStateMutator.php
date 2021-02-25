<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\TestExecuteCompleteEvent;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Event\TestFailedEvent;
use App\Model\Document\Step;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\EntityPersister;

class TestStateMutator implements EventSubscriberInterface
{
    public function __construct(
        private EntityPersister $entityPersister,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            TestExecuteCompleteEvent::class => [
                ['setCompleteFromTestExecuteCompleteEvent', 100],
            ],
            TestExecuteDocumentReceivedEvent::class => [
                ['setFailedFromTestExecuteDocumentReceivedEvent', 50],
            ],
        ];
    }

    public function setCompleteFromTestExecuteCompleteEvent(TestExecuteCompleteEvent $event): void
    {
        $this->setComplete($event->getTest());
    }

    public function setFailedFromTestExecuteDocumentReceivedEvent(TestExecuteDocumentReceivedEvent $event): void
    {
        $document = $event->getDocument();

        $step = new Step($document);
        if ($step->isStep() && $step->statusIsFailed()) {
            $test = $event->getTest();

            $this->setFailed($test);
            $this->eventDispatcher->dispatch(new TestFailedEvent($test));
        }
    }

    public function setRunning(Test $test): void
    {
        $this->set($test, Test::STATE_RUNNING);
    }

    public function setComplete(Test $test): void
    {
        if (Test::STATE_RUNNING === $test->getState()) {
            $this->set($test, Test::STATE_COMPLETE);
        }
    }

    public function setFailed(Test $test): void
    {
        $this->set($test, Test::STATE_FAILED);
    }

    public function setCancelled(Test $test): void
    {
        $this->set($test, Test::STATE_CANCELLED);
    }

    /**
     * @param Test $test
     * @param Test::STATE_* $state
     */
    private function set(Test $test, string $state): void
    {
        $test->setState($state);
        $this->entityPersister->persist($test);
    }
}
