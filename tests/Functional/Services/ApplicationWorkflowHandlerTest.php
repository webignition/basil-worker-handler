<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\TestFailedEvent;
use App\Event\TestPassedEvent;
use App\MessageDispatcher\SendCallbackMessageDispatcher;
use App\Services\ApplicationWorkflowHandler;
use App\Services\ExecutionWorkflowHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Entity\MockTest;
use App\Tests\Mock\MockEventDispatcher;
use App\Tests\Mock\Services\MockApplicationState;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $sendCallbackMessageDispatcher = self::$container->get(SendCallbackMessageDispatcher::class);
        if ($sendCallbackMessageDispatcher instanceof SendCallbackMessageDispatcher) {
            $this->eventDispatcher->removeListener(
                TestPassedEvent::class,
                [
                    $sendCallbackMessageDispatcher,
                    'dispatchForEvent'
                ]
            );

            $this->eventDispatcher->removeListener(
                TestFailedEvent::class,
                [
                    $sendCallbackMessageDispatcher,
                    'dispatchForEvent'
                ]
            );
        }

        $executionWorkflowHandler = self::$container->get(ExecutionWorkflowHandler::class);
        if ($executionWorkflowHandler instanceof ExecutionWorkflowHandler) {
            $this->eventDispatcher->removeListener(
                TestPassedEvent::class,
                [
                    $executionWorkflowHandler,
                    'dispatchExecutionCompletedEvent'
                ]
            );

            $this->eventDispatcher->removeListener(
                TestPassedEvent::class,
                [
                    $executionWorkflowHandler,
                    'dispatchNextExecuteTestMessageFromTestPassedEvent'
                ]
            );
        }
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
            ->withoutDispatchCall()
            ->getMock();

        ObjectReflector::setProperty(
            $this->handler,
            ApplicationWorkflowHandler::class,
            'eventDispatcher',
            $eventDispatcher
        );

        $applicationState = (new MockApplicationState())
            ->withIsCall(false, ApplicationState::STATE_COMPLETE)
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

    public function testSubscribesToTestFailedEventApplicationComplete(): void
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

        $applicationState = (new MockApplicationState())
            ->withIsCall(false, ApplicationState::STATE_COMPLETE)
            ->getMock();

        ObjectReflector::setProperty(
            $this->handler,
            ApplicationWorkflowHandler::class,
            'applicationState',
            $applicationState
        );

        $this->eventDispatcher->dispatch(new TestFailedEvent(
            (new MockTest())->getMock(),
            new Document(''),
        ));
    }
}
