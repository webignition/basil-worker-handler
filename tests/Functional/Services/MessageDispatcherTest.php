<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Message\CompileSourceMessage;
use App\Message\ExecuteTestMessage;
use App\Message\JobCompletedCheckMessage;
use App\Message\JobReadyMessage;
use App\Message\SendCallbackMessage;
use App\Message\TimeoutCheckMessage;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use webignition\SymfonyMessengerMessageDispatcher\MessageDispatcher;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class MessageDispatcherTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private MessageDispatcher $messageDispatcher;
    private MessengerAsserter $messengerAsserter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider dispatchExpectsNoDelayStampDataProvider
     *
     * @param object $message
     */
    public function testDispatchExpectsNoDelayStamp(object $message): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();

        $this->messageDispatcher->dispatch($message);

        $this->messengerAsserter->assertMessageAtPositionEquals(0, $message);
        $envelope = $this->messengerAsserter->getEnvelopeAtPosition(0);

        $this->messengerAsserter->assertEnvelopeNotContainsStampsOfType($envelope, DelayStamp::class);
    }

    /**
     * @return array[]
     */
    public function dispatchExpectsNoDelayStampDataProvider(): array
    {
        return [
            CompileSourceMessage::class => [
                'message' => new CompileSourceMessage('Test/test.yml'),
            ],
            ExecuteTestMessage::class => [
                'message' => new ExecuteTestMessage(1),
            ],
            JobCompletedCheckMessage::class => [
                'message' => new JobCompletedCheckMessage(),
            ],
            JobReadyMessage::class => [
                'message' => new JobReadyMessage(),
            ],
            SendCallbackMessage::class . ' retry count 0' => [
                'message' => new SendCallbackMessage(1, 0),
                'expectedStamps' => [],
            ],
        ];
    }

    /**
     * @dataProvider dispatchExpectsDelayStampDataProvider
     *
     * @param object $message
     */
    public function testDispatchExpectsDelayStamp(object $message, int $expectedDelayStampValue): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();

        $this->messageDispatcher->dispatch($message);

        $this->messengerAsserter->assertMessageAtPositionEquals(0, $message);
        $envelope = $this->messengerAsserter->getEnvelopeAtPosition(0);

        $this->messengerAsserter->assertEnvelopeContainsStamp(
            $envelope,
            new DelayStamp($expectedDelayStampValue),
            0
        );
    }

    /**
     * @return array[]
     */
    public function dispatchExpectsDelayStampDataProvider(): array
    {
        return [
            TimeoutCheckMessage::class => [
                'message' => new TimeoutCheckMessage(),
                'expectedDelayStampValue' => 30000,
            ],
            SendCallbackMessage::class . ' retry count 1' => [
                'message' => new SendCallbackMessage(1, 1),
                'expectedDelayStampValue' => 1000,
            ],
            SendCallbackMessage::class . ' retry count 2' => [
                'message' => new SendCallbackMessage(1, 2),
                'expectedDelayStampValue' => 3000,
            ],
            SendCallbackMessage::class . ' retry count 3' => [
                'message' => new SendCallbackMessage(1, 3),
                'expectedDelayStampValue' => 7000,
            ],
        ];
    }
}
