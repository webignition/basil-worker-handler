<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\JobTimeoutEvent;
use App\Event\TestStepFailedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\TestRepository;

class TestCanceller implements EventSubscriberInterface
{
    public function __construct(
        private TestStateMutator $testStateMutator,
        private TestRepository $testRepository
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            TestStepFailedEvent::class => [
                ['cancelAwaitingFromTestFailedEvent', 0],
            ],
            JobTimeoutEvent::class => [
                ['cancelUnfinished', 0],
            ],
        ];
    }

    public function cancelAwaitingFromTestFailedEvent(TestStepFailedEvent $event): void
    {
        $this->cancelAwaiting();
    }

    public function cancelUnfinished(): void
    {
        $this->cancelCollection($this->testRepository->findAllUnfinished());
    }

    public function cancelAwaiting(): void
    {
        $this->cancelCollection($this->testRepository->findAllAwaiting());
    }

    /**
     * @param Test[] $tests
     */
    private function cancelCollection(array $tests): void
    {
        foreach ($tests as $test) {
            $this->testStateMutator->setCancelled($test);
        }
    }
}
