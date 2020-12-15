<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Message\JobReadyMessage;
use App\Services\ApplicationState;
use App\Services\CompilationState;
use App\Services\ExecutionState;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\EntityRefresher;
use App\Tests\Services\InvokableFactory\ApplicationStateGetterFactory;
use App\Tests\Services\InvokableFactory\CompilationStateGetterFactory;
use App\Tests\Services\InvokableFactory\ExecutionStateGetterFactory;
use App\Tests\Services\InvokableFactory\HttpLogResetter;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\SourceCreationFactory;
use App\Tests\Services\InvokableFactory\SourceGetterFactory;
use App\Tests\Services\InvokableHandler;
use SebastianBergmann\Timer\Timer;
use Symfony\Component\Messenger\MessageBusInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

abstract class AbstractEndToEndTest extends AbstractBaseIntegrationTest
{
    use TestClassServicePropertyInjectorTrait;

    private const MAX_DURATION_IN_SECONDS = 30;
    private const MICROSECONDS_PER_SECOND = 1000000;

    protected EntityRefresher $entityRefresher;
    protected InvokableHandler $invokableHandler;
    protected ApplicationState $applicationState;
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

        $compilationState = $this->invokableHandler->invoke(CompilationStateGetterFactory::get());
        self::assertSame(CompilationState::STATE_AWAITING, $compilationState);

        $executionState = $this->invokableHandler->invoke(ExecutionStateGetterFactory::get());
        self::assertSame(ExecutionState::STATE_AWAITING, $executionState);

        $this->invokableHandler->invoke(SourceCreationFactory::createFromManifestPath($jobSetup->getManifestPath()));

        $timer = new Timer();
        $timer->start();

        $this->messageBus->dispatch(new JobReadyMessage());

        $sourcePaths = $this->invokableHandler->invoke(SourceGetterFactory::getAllRelativePaths());
        self::assertSame($expectedSourcePaths, $sourcePaths);

        $this->waitUntilApplicationWorkflowIsComplete();

        $duration = $timer->stop();

        self::assertSame(
            $expectedCompilationEndState,
            $this->invokableHandler->invoke(CompilationStateGetterFactory::get())
        );

        self::assertSame(
            $expectedExecutionEndState,
            $this->invokableHandler->invoke(ExecutionStateGetterFactory::get())
        );

        self::assertSame(
            $expectedApplicationEndState,
            $this->invokableHandler->invoke(ApplicationStateGetterFactory::get())
        );

        $this->invokableHandler->invoke($postAssertions);

        self::assertLessThanOrEqual(self::MAX_DURATION_IN_SECONDS, $duration->asSeconds());
    }

    private function waitUntilApplicationWorkflowIsComplete(): bool
    {
        $duration = 0;
        $maxDuration = self::MAX_DURATION_IN_SECONDS * self::MICROSECONDS_PER_SECOND;
        $maxDurationReached = false;
        $intervalInMicroseconds = 100000;

        $applicationFinishedStates = [ApplicationState::STATE_COMPLETE, ApplicationState::STATE_TIMED_OUT];

        while (
            false === $this->applicationState->is(...$applicationFinishedStates) &&
            false === $maxDurationReached
        ) {
            usleep($intervalInMicroseconds);
            $duration += $intervalInMicroseconds;
            $maxDurationReached = $duration >= $maxDuration;

            if ($maxDurationReached) {
                return false;
            }

            $this->entityRefresher->refreshForEntities([
                Job::class,
                Test::class,
                CallbackEntity::class,
            ]);
        }

        return true;
    }
}
