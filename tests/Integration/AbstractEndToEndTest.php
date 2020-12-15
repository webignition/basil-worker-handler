<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Message\JobReadyMessage;
use App\Services\ApplicationState;
use App\Services\CompilationState;
use App\Services\ExecutionState;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\InvokableFactory\ApplicationStateAsserter;
use App\Tests\Services\InvokableFactory\CompilationStateAsserter;
use App\Tests\Services\InvokableFactory\ExecutionStateAsserter;
use App\Tests\Services\InvokableFactory\HttpLogResetter;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\SourceCreationFactory;
use App\Tests\Services\InvokableFactory\SourceGetterFactory;
use App\Tests\Services\InvokableFactory\WaitUntilApplicationStateIs;
use App\Tests\Services\InvokableHandler;
use SebastianBergmann\Timer\Timer;
use Symfony\Component\Messenger\MessageBusInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

abstract class AbstractEndToEndTest extends AbstractBaseIntegrationTest
{
    use TestClassServicePropertyInjectorTrait;

    private const MAX_DURATION_IN_SECONDS = 30;

    protected InvokableHandler $invokableHandler;
    protected MessageBusInterface $messageBus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    protected function tearDown(): void
    {
        $this->invokableHandler->invoke(HttpLogResetter::reset());

        parent::tearDown();
    }

    /**
     * @param JobSetup $jobSetup
     * @param string[] $expectedSourcePaths
     * @param CompilationState::STATE_* $expectedCompilationEndState
     * @param ExecutionState::STATE_* $expectedExecutionEndState
     * @param ApplicationState::STATE_* $expectedApplicationEndState
     * @param InvokableInterface $postAssertions
     */
    protected function doCreateJobAddSourcesTest(
        JobSetup $jobSetup,
        array $expectedSourcePaths,
        string $expectedCompilationEndState,
        string $expectedExecutionEndState,
        string $expectedApplicationEndState,
        InvokableInterface $postAssertions
    ): void {
        $this->invokableHandler->invoke(JobSetupInvokableFactory::setup($jobSetup));

        $this->invokableHandler->invoke(CompilationStateAsserter::is(CompilationState::STATE_AWAITING));
        $this->invokableHandler->invoke(ExecutionStateAsserter::is(ExecutionState::STATE_AWAITING));

        $this->invokableHandler->invoke(SourceCreationFactory::createFromManifestPath($jobSetup->getManifestPath()));

        $timer = new Timer();
        $timer->start();

        $this->messageBus->dispatch(new JobReadyMessage());

        $sourcePaths = $this->invokableHandler->invoke(SourceGetterFactory::getAllRelativePaths());
        self::assertSame($expectedSourcePaths, $sourcePaths);

        $this->invokableHandler->invoke(WaitUntilApplicationStateIs::create([
            ApplicationState::STATE_COMPLETE,
            ApplicationState::STATE_TIMED_OUT,
        ]));

        $duration = $timer->stop();

        $this->invokableHandler->invoke(CompilationStateAsserter::is($expectedCompilationEndState));
        $this->invokableHandler->invoke(ExecutionStateAsserter::is($expectedExecutionEndState));
        $this->invokableHandler->invoke(ApplicationStateAsserter::is($expectedApplicationEndState));

        $this->invokableHandler->invoke($postAssertions);

        self::assertLessThanOrEqual(self::MAX_DURATION_IN_SECONDS, $duration->asSeconds());
    }
}
