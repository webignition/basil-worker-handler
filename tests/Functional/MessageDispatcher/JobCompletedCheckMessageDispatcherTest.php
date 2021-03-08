<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Message\JobCompleteCheckMessage;
use App\MessageDispatcher\JobCompletedCheckMessageDispatcher;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class JobCompletedCheckMessageDispatcherTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private JobCompletedCheckMessageDispatcher $messageDispatcher;
    private EventDispatcherInterface $eventDispatcher;
    private MessengerAsserter $messengerAsserter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }


    public function testDispatch(): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();

        $this->messageDispatcher->dispatch();

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, new JobCompleteCheckMessage());

        $jobCompletedCheckPeriod = self::$container->getParameter('job_completed_check_period');
        if (is_string($jobCompletedCheckPeriod)) {
            $jobCompletedCheckPeriod = (int) $jobCompletedCheckPeriod;
        }

        if (!is_int($jobCompletedCheckPeriod)) {
            $jobCompletedCheckPeriod = 0;
        }

        $expectedDelayStamp = new DelayStamp(
            $jobCompletedCheckPeriod *
            JobCompletedCheckMessageDispatcher::MILLISECONDS_PER_SECOND
        );

        $this->messengerAsserter->assertEnvelopeContainsStamp(
            $this->messengerAsserter->getEnvelopeAtPosition(0),
            $expectedDelayStamp,
            0
        );
    }
}
