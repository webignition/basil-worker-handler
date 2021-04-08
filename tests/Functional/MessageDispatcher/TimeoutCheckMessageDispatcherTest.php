<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Event\JobReadyEvent;
use App\Message\TimeoutCheckMessage;
use App\MessageDispatcher\SendCallbackMessageDispatcher;
use App\MessageDispatcher\TimeoutCheckMessageDispatcher;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\EventListenerRemover;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class TimeoutCheckMessageDispatcherTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private TimeoutCheckMessageDispatcher $messageDispatcher;
    private EventDispatcherInterface $eventDispatcher;
    private MessengerAsserter $messengerAsserter;
    private EventListenerRemover $eventListenerRemover;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $this->eventListenerRemover->remove([
            SendCallbackMessageDispatcher::class => [
                JobReadyEvent::class => ['dispatchForEvent'],
            ],
        ]);
    }


    public function testDispatch(): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();

        $this->messageDispatcher->dispatch();

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, new TimeoutCheckMessage());

        $jobTimeoutCheckPeriod = self::$container->getParameter('job_timeout_check_period_ms');
        if (is_string($jobTimeoutCheckPeriod)) {
            $jobTimeoutCheckPeriod = (int) $jobTimeoutCheckPeriod;
        }

        if (!is_int($jobTimeoutCheckPeriod)) {
            $jobTimeoutCheckPeriod = 0;
        }

        $expectedDelayStamp = new DelayStamp($jobTimeoutCheckPeriod);

        $this->messengerAsserter->assertEnvelopeContainsStamp(
            $this->messengerAsserter->getEnvelopeAtPosition(0),
            $expectedDelayStamp,
            0
        );
    }

    /**
     * @dataProvider subscribesToEventDataProvider
     */
    public function testSubscribesToEvent(Event $event, TimeoutCheckMessage $expectedQueuedMessage): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();

        $this->eventDispatcher->dispatch($event);

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            $expectedQueuedMessage
        );
    }

    /**
     * @return array[]
     */
    public function subscribesToEventDataProvider(): array
    {
        return [
            JobReadyEvent::class => [
                'event' => new JobReadyEvent(),
                'expectedQueuedMessage' => new TimeoutCheckMessage(),
            ],
        ];
    }
}
