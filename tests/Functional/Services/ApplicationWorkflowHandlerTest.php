<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\JobCompletedEvent;
use App\Event\JobFailedEvent;
use App\Event\TestFailedEvent;
use App\Event\TestPassedEvent;
use App\MessageDispatcher\SendCallbackMessageDispatcher;
use App\Services\ApplicationWorkflowHandler;
use App\Services\ExecutionWorkflowHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Entity\MockTest;
use App\Tests\Mock\MockEventDispatcher;
use App\Tests\Mock\Services\MockApplicationState;
use App\Tests\Model\ExpectedDispatchedEvent;
use App\Tests\Model\ExpectedDispatchedEventCollection;
use App\Tests\Services\EventListenerRemover;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\StateBundle\Services\ApplicationState;
use webignition\ObjectReflector\ObjectReflector;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\YamlDocument\Document;

class ApplicationWorkflowHandlerTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;
    use MockeryPHPUnitIntegration;

    private ApplicationWorkflowHandler $handler;
    private EventDispatcherInterface $eventDispatcher;
    private EventListenerRemover $eventListenerRemover;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $this->eventListenerRemover->removeServiceMethodsForEvents(
            SendCallbackMessageDispatcher::class,
            [
                TestPassedEvent::class => ['dispatchForEvent'],
                TestFailedEvent::class => ['dispatchForEvent'],
            ]
        );

        $this->eventListenerRemover->removeServiceMethodsForEvents(
            ExecutionWorkflowHandler::class,
            [
                TestPassedEvent::class => [
                    'dispatchExecutionCompletedEvent',
                    'dispatchNextExecuteTestMessageFromTestPassedEvent',
                ],
            ]
        );
    }

    public function testSubscribesToTestPassedEventApplicationNotComplete(): void
    {
        $eventDispatcher = (new MockEventDispatcher())
            ->withoutDispatchCall()
            ->getMock();

        ObjectReflector::setProperty(
            $this->handler,
            ApplicationWorkflowHandler::class,
            'eventDispatcher',
            $eventDispatcher
        );

        $this->eventDispatcher->dispatch(new TestPassedEvent(
            (new MockTest())->getMock(),
            new Document(''),
        ));
    }

    public function testSubscribesToTestPassedEventApplicationComplete(): void
    {
        $eventDispatcher = (new MockEventDispatcher())
            ->withDispatchCalls(new ExpectedDispatchedEventCollection([
                new ExpectedDispatchedEvent(function (Event $event) {
                    self::assertInstanceOf(JobCompletedEvent::class, $event);

                    return true;
                }),
            ]))
            ->getMock();

        ObjectReflector::setProperty(
            $this->handler,
            ApplicationWorkflowHandler::class,
            'eventDispatcher',
            $eventDispatcher
        );

        $applicationState = (new MockApplicationState())
            ->withIsCall(true, ApplicationState::STATE_COMPLETE)
            ->getMock();

        ObjectReflector::setProperty(
            $this->handler,
            ApplicationWorkflowHandler::class,
            'applicationState',
            $applicationState
        );

        $this->eventDispatcher->dispatch(new TestPassedEvent(
            (new MockTest())->getMock(),
            new Document(''),
        ));
    }

    public function testSubscribesToTestFailedEvent(): void
    {
        $eventDispatcher = (new MockEventDispatcher())
            ->withDispatchCalls(new ExpectedDispatchedEventCollection([
                new ExpectedDispatchedEvent(function (Event $event) {
                    self::assertInstanceOf(JobFailedEvent::class, $event);

                    return true;
                }),
            ]))
            ->getMock();

        ObjectReflector::setProperty(
            $this->handler,
            ApplicationWorkflowHandler::class,
            'eventDispatcher',
            $eventDispatcher
        );

        $this->eventDispatcher->dispatch(new TestFailedEvent(
            (new MockTest())->getMock(),
            new Document(''),
        ));
    }
}
