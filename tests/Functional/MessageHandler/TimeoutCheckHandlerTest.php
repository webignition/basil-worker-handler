<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Event\JobTimeoutEvent;
use App\Message\TimeoutCheckMessage;
use App\MessageHandler\TimeoutCheckHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Entity\MockJob;
use App\Tests\Mock\MockEventDispatcher;
use App\Tests\Mock\Services\MockJobStore;
use App\Tests\Model\ExpectedDispatchedEvent;
use App\Tests\Model\ExpectedDispatchedEventCollection;
use App\Tests\Services\Asserter\MessengerAsserter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\ObjectReflector\ObjectReflector;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class TimeoutCheckHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private TimeoutCheckHandler $handler;
    private MessengerAsserter $messengerAsserter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testInvokeNoJob(): void
    {
        $eventDispatcher = (new MockEventDispatcher())
            ->withoutDispatchCall()
            ->getMock();

        ObjectReflector::setProperty($this->handler, TimeoutCheckHandler::class, 'eventDispatcher', $eventDispatcher);

        $message = new TimeoutCheckMessage();

        ($this->handler)($message);

        $this->messengerAsserter->assertQueueCount(0);
    }

    public function testInvokeJobMaximumDurationNotReached(): void
    {
        $eventDispatcher = (new MockEventDispatcher())
            ->withoutDispatchCall()
            ->getMock();

        $job = (new MockJob())
            ->withHasReachedMaximumDurationCall(false)
            ->getMock();

        $jobStore = (new MockJobStore())
            ->withHasCall(true)
            ->withGetCall($job)
            ->getMock();

        ObjectReflector::setProperty($this->handler, TimeoutCheckHandler::class, 'eventDispatcher', $eventDispatcher);
        ObjectReflector::setProperty($this->handler, TimeoutCheckHandler::class, 'jobStore', $jobStore);

        $message = new TimeoutCheckMessage();

        ($this->handler)($message);

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, new TimeoutCheckMessage());
        $this->messengerAsserter->assertEnvelopeContainsStamp(
            $this->messengerAsserter->getEnvelopeAtPosition(0),
            new DelayStamp(30000),
            0
        );
    }

    public function testInvokeJobMaximumDurationReached(): void
    {
        $jobMaximumDuration = 123;

        $eventDispatcher = (new MockEventDispatcher())
            ->withDispatchCalls(new ExpectedDispatchedEventCollection([
                new ExpectedDispatchedEvent(
                    function (Event $actualEvent) use ($jobMaximumDuration) {
                        self::assertInstanceOf(JobTimeoutEvent::class, $actualEvent);

                        if ($actualEvent instanceof JobTimeoutEvent) {
                            self::assertSame($jobMaximumDuration, $actualEvent->getJobMaximumDuration());
                        }

                        return true;
                    },
                ),
            ]))
            ->getMock();

        $job = (new MockJob())
            ->withHasReachedMaximumDurationCall(true)
            ->withGetMaximumDurationInSecondsCall($jobMaximumDuration)
            ->getMock();

        $jobStore = (new MockJobStore())
            ->withHasCall(true)
            ->withGetCall($job)
            ->getMock();

        ObjectReflector::setProperty($this->handler, TimeoutCheckHandler::class, 'eventDispatcher', $eventDispatcher);
        ObjectReflector::setProperty($this->handler, TimeoutCheckHandler::class, 'jobStore', $jobStore);

        $message = new TimeoutCheckMessage();

        ($this->handler)($message);

        $this->messengerAsserter->assertQueueCount(0);
    }
}
