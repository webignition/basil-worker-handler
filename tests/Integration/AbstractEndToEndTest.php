<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Event\JobReadyEvent;
use App\Services\ApplicationState;
use App\Services\CompilationState;
use App\Services\ExecutionState;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\EntityRefresher;
use App\Tests\Services\Integration\HttpLogReader;
use App\Tests\Services\InvokableFactory\ApplicationStateGetterFactory;
use App\Tests\Services\InvokableFactory\CompilationStateGetterFactory;
use App\Tests\Services\InvokableFactory\ExecutionStateGetterFactory;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\SourceGetterFactory;
use App\Tests\Services\InvokableHandler;
use App\Tests\Services\SourceFileStoreInitializer;
use App\Tests\Services\UploadedFileFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use SebastianBergmann\Timer\Timer;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;
use webignition\BasilWorker\PersistenceBundle\Entity\Source;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\JobFactory;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\SourceFactory;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

abstract class AbstractEndToEndTest extends AbstractBaseIntegrationTest
{
    use TestClassServicePropertyInjectorTrait;

    private const MAX_DURATION_IN_SECONDS = 30;
    private const MICROSECONDS_PER_SECOND = 1000000;

    protected JobStore $jobStore;
    protected UploadedFileFactory $uploadedFileFactory;
    protected BasilFixtureHandler $basilFixtureHandler;
    protected EntityRefresher $entityRefresher;
    protected HttpLogReader $httpLogReader;
    protected InvokableHandler $invokableHandler;
    protected ApplicationState $applicationState;
    protected JobFactory $jobFactory;
    protected SourceFactory $sourceFactory;
    protected EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
        $this->initializeSourceFileStore();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->httpLogReader->reset();
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
        $this->jobFactory->create(
            $jobSetup->getLabel(),
            $jobSetup->getCallbackUrl(),
            $jobSetup->getMaximumDurationInSeconds()
        );

        self::assertSame(
            CompilationState::STATE_AWAITING,
            $this->invokableHandler->invoke(CompilationStateGetterFactory::get())
        );

        $timer = new Timer();
        $timer->start();

        $this->createJobSources($jobSetup->getManifestPath());
        $this->eventDispatcher->dispatch(new JobReadyEvent());

        self::assertSame(
            $expectedSourcePaths,
            $this->invokableHandler->invoke(SourceGetterFactory::getAllRelativePaths())
        );

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

    /**
     * @param string $manifestPath
     *
     * @return array<string, Source>
     */
    protected function createJobSources(string $manifestPath): array
    {
        $manifestContent = (string) file_get_contents($manifestPath);
        $sourcePaths = array_filter(explode("\n", $manifestContent));

        $this->basilFixtureHandler->createUploadFileCollection($sourcePaths);

        $sources = [];
        foreach ($sourcePaths as $sourcePath) {
            $sourceType = substr_count($sourcePath, 'Test/') === 0
                ? Source::TYPE_RESOURCE
                : Source::TYPE_TEST;

            $sources[$sourcePath] = $this->sourceFactory->create($sourceType, $sourcePath);
        }

        return $sources;
    }

    private function initializeSourceFileStore(): void
    {
        $sourceFileStoreInitializer = self::$container->get(SourceFileStoreInitializer::class);
        self::assertInstanceOf(SourceFileStoreInitializer::class, $sourceFileStoreInitializer);
        if ($sourceFileStoreInitializer instanceof SourceFileStoreInitializer) {
            $sourceFileStoreInitializer->initialize();
        }
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
