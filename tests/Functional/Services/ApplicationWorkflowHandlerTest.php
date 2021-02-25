<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\TestExecuteCompleteEvent;
use App\Message\SendCallbackMessage;
use App\Services\ApplicationWorkflowHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\InvokableFactory\CallbackGetterFactory;
use App\Tests\Services\InvokableFactory\CallbackSetup;
use App\Tests\Services\InvokableFactory\CallbackSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\SourceCreationFactory;
use App\Tests\Services\InvokableFactory\TestGetterFactory;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use Psr\EventDispatcher\EventDispatcherInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class ApplicationWorkflowHandlerTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private ApplicationWorkflowHandler $handler;
    private EventDispatcherInterface $eventDispatcher;
    private MessengerAsserter $messengerAsserter;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testDispatchJobCompleteEventNoMessageIsDispatched(): void
    {
        $this->handler->dispatchJobCompleteEvent();
        $this->messengerAsserter->assertQueueIsEmpty();
    }

    /**
     * @dataProvider dispatchJobCompleteEventMessageDispatchedDataProvider
     */
    public function testDispatchJobCompleteEventMessageDispatched(InvokableInterface $setup): void
    {
        $this->doJobCompleteEventDrivenTest(
            $setup,
            function () {
                $this->handler->dispatchJobCompleteEvent();
            }
        );
    }

    /**
     * @return array[]
     */
    public function dispatchJobCompleteEventMessageDispatchedDataProvider(): array
    {
        $jobSetup = (new JobSetup())
            ->withLabel('label')
            ->withCallbackUrl('http://example.com/callback')
            ->withManifestPath(getcwd() . '/tests/Fixtures/Manifest/manifest-chrome-open-index.txt');

        return [
            'job with single complete test' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup($jobSetup),
                    SourceCreationFactory::createFromManifestPath($jobSetup->getManifestPath()),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/chrome-open-index.yml')
                            ->withState(Test::STATE_COMPLETE),
                    ]),
                    CallbackSetupInvokableFactory::setup(
                        (new CallbackSetup())
                            ->withType(CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED)
                            ->withState(CallbackInterface::STATE_COMPLETE)
                    ),
                ]),
            ],
        ];
    }

    public function testSubscribesToTestExecuteCompleteEvent(): void
    {
        $jobSetup = (new JobSetup())
            ->withLabel('label')
            ->withCallbackUrl('http://example.com/callback')
            ->withManifestPath(getcwd() . '/tests/Fixtures/Manifest/manifest-chrome-open-index.txt');

        $this->doJobCompleteEventDrivenTest(
            new InvokableCollection([
                JobSetupInvokableFactory::setup($jobSetup),
                SourceCreationFactory::createFromManifestPath($jobSetup->getManifestPath()),
                TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/Test/chrome-open-index.yml')
                        ->withState(Test::STATE_COMPLETE),
                ]),
                CallbackSetupInvokableFactory::setup(
                    (new CallbackSetup())
                        ->withType(CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED)
                        ->withState(CallbackInterface::STATE_COMPLETE)
                ),
            ]),
            function (TestExecuteCompleteEvent $event) {
                $this->eventDispatcher->dispatch($event);
            }
        );
    }

    private function doJobCompleteEventDrivenTest(InvokableInterface $setup, callable $execute): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();
        $this->invokableHandler->invoke($setup);

        $tests = $this->invokableHandler->invoke(TestGetterFactory::getAll());
        $test = array_pop($tests);
        $testCompleteEvent = new TestExecuteCompleteEvent($test);

        $execute($testCompleteEvent);

        $this->messengerAsserter->assertQueueCount(1);

        $callbacks = $this->invokableHandler->invoke(CallbackGetterFactory::getAll());


        $latestCallback = array_pop($callbacks);

        self::assertInstanceOf(CallbackInterface::class, $latestCallback);
        self::assertSame(CallbackInterface::TYPE_JOB_COMPLETE, $latestCallback->getType());

        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            new SendCallbackMessage((int) $latestCallback->getId())
        );
    }
}
