<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Message\SendCallbackMessage;
use App\MessageHandler\SendCallbackHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockCallbackSender;
use App\Tests\Mock\Services\MockCallbackStateMutator;
use App\Tests\Services\InvokableFactory\CallbackSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\CallbackRepository;
use webignition\ObjectReflector\ObjectReflector;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class SendCallbackHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private SendCallbackHandler $handler;
    private CallbackRepository $callbackRepository;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testInvokeCallbackNotExists()
    {
        $callback = \Mockery::mock(CallbackInterface::class);
        $callback
            ->shouldReceive('getId')
            ->andReturn(0);

        $stateMutator = (new MockCallbackStateMutator())
            ->withoutSetSendingCall()
            ->getMock();

        $sender = (new MockCallbackSender())
            ->withoutSendCall()
            ->getMock();

        ObjectReflector::setProperty($this->handler, SendCallbackHandler::class, 'stateMutator', $stateMutator);
        ObjectReflector::setProperty($this->handler, SendCallbackHandler::class, 'sender', $sender);

        $message = new SendCallbackMessage((int) $callback->getId());

        ($this->handler)($message);
    }

    public function testInvokeCallbackExists()
    {
        $callback = $this->invokableHandler->invoke(CallbackSetupInvokableFactory::setup());

        $mockSender = new MockCallbackSender();

        $expectedSentCallback = $this->callbackRepository->find($callback->getId());

        if ($expectedSentCallback instanceof CallbackInterface) {
            $expectedSentCallback->setState(CallbackInterface::STATE_SENDING);
            $mockSender = $mockSender->withSendCall($expectedSentCallback);
        }

        ObjectReflector::setProperty($this->handler, SendCallbackHandler::class, 'sender', $mockSender->getMock());

        $message = new SendCallbackMessage((int) $callback->getId());

        ($this->handler)($message);
    }
}
