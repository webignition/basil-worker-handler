<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\TestExecuteCompleteEvent;
use App\Event\TestExecuteDocumentReceivedEvent;
use App\Services\CallbackEventFactory;
use App\Services\TestStateMutator;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\InvokableFactory\TestMutatorFactory;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use Psr\EventDispatcher\EventDispatcherInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\YamlDocument\Document;

class TestStateMutatorTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private TestStateMutator $mutator;
    private EventDispatcherInterface $eventDispatcher;
    private Test $test;
    private CallbackEventFactory $callbackEventFactory;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $this->test = $this->invokableHandler->invoke(TestSetupInvokableFactory::setup());
    }

    /**
     * @dataProvider setCompleteDataProvider
     *
     * @param Test::STATE_* $initialState
     * @param Test::STATE_* $expectedState
     */
    public function testSetComplete(string $initialState, string $expectedState): void
    {
        $this->invokableHandler->invoke(TestMutatorFactory::createSetState($this->test, $initialState));
        self::assertSame($initialState, $this->test->getState());

        $this->mutator->setComplete($this->test);

        self::assertSame($expectedState, $this->test->getState());
    }

    /**
     * @return array[]
     */
    public function setCompleteDataProvider(): array
    {
        return [
            Test::STATE_AWAITING => [
                'initialState' => Test::STATE_AWAITING,
                'expectedState' => Test::STATE_AWAITING,
            ],
            Test::STATE_RUNNING => [
                'initialState' => Test::STATE_RUNNING,
                'expectedState' => Test::STATE_COMPLETE,
            ],
            Test::STATE_COMPLETE => [
                'initialState' => Test::STATE_COMPLETE,
                'expectedState' => Test::STATE_COMPLETE,
            ],
            Test::STATE_FAILED => [
                'initialState' => Test::STATE_FAILED,
                'expectedState' => Test::STATE_FAILED,
            ],
            Test::STATE_CANCELLED => [
                'initialState' => Test::STATE_CANCELLED,
                'expectedState' => Test::STATE_CANCELLED,
            ],
        ];
    }

    public function testSetCompleteFromTestExecuteCompleteEvent(): void
    {
        $this->doTestExecuteCompleteEventDrivenTest(function (TestExecuteCompleteEvent $event) {
            $this->mutator->setCompleteFromTestExecuteCompleteEvent($event);
        });
    }

    public function testSubscribesToTestExecuteCompleteEvent(): void
    {
        $this->doTestExecuteCompleteEventDrivenTest(function (TestExecuteCompleteEvent $event) {
            $this->eventDispatcher->dispatch($event);
        });
    }

    private function doTestExecuteCompleteEventDrivenTest(callable $callable): void
    {
        $this->invokableHandler->invoke(TestMutatorFactory::createSetState($this->test, Test::STATE_RUNNING));

        $event = new TestExecuteCompleteEvent($this->test);

        $callable($event);

        self::assertSame(Test::STATE_COMPLETE, $this->test->getState());
    }

    /**
     * @dataProvider handleTestExecuteDocumentReceivedEventDataProvider
     */
    public function testSetFailedFromTestExecuteDocumentReceivedEvent(Document $document, string $expectedState): void
    {
        $this->doTestExecuteDocumentReceivedEventDrivenTest(
            $document,
            $expectedState,
            function (TestExecuteDocumentReceivedEvent $event) {
                $this->mutator->setFailedFromTestExecuteDocumentReceivedEvent($event);
            }
        );
    }

    /**
     * @dataProvider handleTestExecuteDocumentReceivedEventDataProvider
     */
    public function testSubscribesToTestExecuteDocumentReceivedEvent(Document $document, string $expectedState): void
    {
        $this->doTestExecuteDocumentReceivedEventDrivenTest(
            $document,
            $expectedState,
            function (TestExecuteDocumentReceivedEvent $event) {
                $this->eventDispatcher->dispatch($event);
            }
        );
    }

    private function doTestExecuteDocumentReceivedEventDrivenTest(
        Document $document,
        string $expectedState,
        callable $execute
    ): void {
        self::assertSame(Test::STATE_AWAITING, $this->test->getState());

        $event = new TestExecuteDocumentReceivedEvent($this->test, $document);
        $execute($event);

        self::assertSame($expectedState, $this->test->getState());
    }

    /**
     * @return array[]
     */
    public function handleTestExecuteDocumentReceivedEventDataProvider(): array
    {
        return [
            'not a step' => [
                'document' => new Document('{ type: test }'),
                'expectedState' => Test::STATE_AWAITING,
            ],
            'step passed' => [
                'document' => new Document('{ type: step, status: passed }'),
                'expectedState' => Test::STATE_AWAITING,
            ],
            'step failed' => [
                'document' => new Document('{ type: step, status: failed }'),
                'expectedState' => Test::STATE_FAILED,
            ],
        ];
    }
}
