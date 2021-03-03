<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\TestFinishedEvent;
use App\Event\TestStepFailedEvent;
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
            TestFinishedEvent::class => [
                ['setCompleteFromTestFinishedEvent', 100],
            ],
            TestStepFailedEvent::class => [
                ['setFailedFromTestStepFailedEvent', 50],
            ],
        ];
    }

    public function setCompleteFromTestFinishedEvent(TestFinishedEvent $event): void
    {
        $this->setComplete($event->getTest());
    }

    public function setFailedFromTestStepFailedEvent(TestStepFailedEvent $event): void
    {
        $this->setFailed($event->getTest());
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
