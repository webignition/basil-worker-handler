<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\JobReadyEvent;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Message\CompileSource;
use App\Message\TimeoutCheck;
use App\Services\CompilationWorkflowHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockSuiteManifest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\SourceSetup;
use App\Tests\Services\InvokableFactory\SourceSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CompilationWorkflowHandlerTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CompilationWorkflowHandler $handler;
    private EventDispatcherInterface $eventDispatcher;
    private MessengerAsserter $messengerAsserter;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider dispatchNextCompileSourceMessageNoMessageDispatchedDataProvider
     */
    public function testDispatchNextCompileSourceMessageNoMessageDispatched(InvokableInterface $setup)
    {
        $this->invokableHandler->invoke($setup);

        $this->handler->dispatchNextCompileSourceMessage();

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    public function dispatchNextCompileSourceMessageNoMessageDispatchedDataProvider(): array
    {
        return [
            'no sources' => [
                'setup' => Invokable::createEmpty(),
            ],
            'no non-compiled sources' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml'),
                    ])
                ]),
            ],
        ];
    }

    /**
     * @dataProvider dispatchNextCompileSourceMessageMessageDispatchedDataProvider
     */
    public function testDispatchNextCompileSourceMessageMessageDispatched(
        InvokableInterface $setup,
        CompileSource $expectedQueuedMessage
    ) {
        $this->invokableHandler->invoke($setup);

        $this->handler->dispatchNextCompileSourceMessage();

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedQueuedMessage);
    }

    public function dispatchNextCompileSourceMessageMessageDispatchedDataProvider(): array
    {
        return [
            'no sources compiled' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath('Test/test1.yml'),
                        (new SourceSetup())
                            ->withPath('Test/test2.yml'),
                    ]),
                ]),
                'expectedQueuedMessage' => new CompileSource('Test/test1.yml'),
            ],
            'all but one sources compiled' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath('Test/test1.yml'),
                        (new SourceSetup())
                            ->withPath('Test/test2.yml'),
                    ]),
                    'create tests' => TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())->withSource('/app/source/Test/test1.yml')
                    ]),
                ]),
                'expectedQueuedMessage' => new CompileSource('Test/test2.yml'),
            ],
        ];
    }

    /**
     * @dataProvider subscribesToEventsDataProvider
     *
     * @param object[] $expectedQueuedMessages
     */
    public function testSubscribesToEvents(Event $event, array $expectedQueuedMessages)
    {
        $this->invokableHandler->invoke(new InvokableCollection([
            'create job' => JobSetupInvokableFactory::setup(),
            'add job sources' => SourceSetupInvokableFactory::setupCollection([
                (new SourceSetup())
                    ->withPath('Test/test1.yml'),
                (new SourceSetup())
                    ->withPath('Test/test2.yml'),
            ]),
        ]));

        $this->messengerAsserter->assertQueueIsEmpty();

        $this->eventDispatcher->dispatch($event);

        $this->messengerAsserter->assertQueueCount(count($expectedQueuedMessages));
        foreach ($expectedQueuedMessages as $messageIndex => $expectedQueuedMessage) {
            $this->messengerAsserter->assertMessageAtPositionEquals($messageIndex, $expectedQueuedMessage);
        }
    }

    public function subscribesToEventsDataProvider(): array
    {
        return [
            SourceCompileSuccessEvent::class => [
                'event' => new SourceCompileSuccessEvent(
                    '/app/source/Test/test1.yml',
                    (new MockSuiteManifest())
                        ->withGetTestManifestsCall([])
                        ->getMock()
                ),
                'expectedQueuedMessages' => [
                    new CompileSource('Test/test1.yml'),
                ],
            ],
            JobReadyEvent::class => [
                'event' => new JobReadyEvent(),
                'expectedQueuedMessages' => [
                    new CompileSource('Test/test1.yml'),
                    new TimeoutCheck(),
                ],
            ],
        ];
    }
}
