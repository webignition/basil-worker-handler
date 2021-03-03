<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\TestFinishedEvent;
use App\Event\TestStepFailedEvent;
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
        $this->doTestExecuteCompleteEventDrivenTest(function (TestFinishedEvent $event) {
            $this->mutator->setCompleteFromTestExecuteCompleteEvent($event);
        });
    }

    public function testSubscribesToTestExecuteCompleteEvent(): void
    {
        $this->doTestExecuteCompleteEventDrivenTest(function (TestFinishedEvent $event) {
            $this->eventDispatcher->dispatch($event);
        });
    }

    private function doTestExecuteCompleteEventDrivenTest(callable $callable): void
    {
        $this->invokableHandler->invoke(TestMutatorFactory::createSetState($this->test, Test::STATE_RUNNING));

        $event = new TestFinishedEvent($this->test, \Mockery::mock(Document::class));

        $callable($event);

        self::assertSame(Test::STATE_COMPLETE, $this->test->getState());
    }

    /**
     * @dataProvider handleTestStepFailedEventDataProvider
     */
    public function testSetFailedFromTestStepFailedEventEvent(Document $document, string $expectedState): void
    {
        $this->doTestExecuteDocumentReceivedEventDrivenTest(
            $document,
            $expectedState,
            function (TestStepFailedEvent $event) {
                $this->mutator->setFailedFromTestStepFailedEvent($event);
            }
        );
    }

    /**
     * @dataProvider handleTestStepFailedEventDataProvider
     */
    public function testSubscribesToTestStepFailedEvent(Document $document, string $expectedState): void
    {
        $this->doTestExecuteDocumentReceivedEventDrivenTest(
            $document,
            $expectedState,
            function (TestStepFailedEvent $event) {
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

        $event = new TestStepFailedEvent($this->test, $document);
        $execute($event);

        self::assertSame($expectedState, $this->test->getState());
    }

    /**
     * @return array[]
     */
    public function handleTestStepFailedEventDataProvider(): array
    {
        return [
            'step failed' => [
                'document' => new Document('{ type: step, status: failed }'),
                'expectedState' => Test::STATE_FAILED,
            ],
        ];
    }
}
