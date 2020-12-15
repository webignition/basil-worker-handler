<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous\EndToEnd;

use App\Services\ApplicationState;
use App\Services\CompilationState;
use App\Services\ExecutionState;
use App\Tests\Integration\AbstractEndToEndTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\Integration\HttpLogReader;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\TestGetterFactory;
use Psr\Http\Message\RequestInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\TestRepository;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CompileExecuteTest extends AbstractEndToEndTest
{
    use TestClassServicePropertyInjectorTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider createAddSourcesCompileExecuteDataProvider
     *
     * @param JobSetup $jobSetup
     * @param string[] $expectedSourcePaths
     * @param CompilationState::STATE_* $expectedCompilationEndState
     * @param ExecutionState::STATE_* $expectedExecutionEndState
     * @param ApplicationState::STATE_* $expectedApplicationEndState
     */
    public function testCreateAddSourcesCompileExecute(
        JobSetup $jobSetup,
        array $expectedSourcePaths,
        string $expectedCompilationEndState,
        string $expectedExecutionEndState,
        string $expectedApplicationEndState,
        InvokableInterface $assertions
    ) {
        $this->doCreateJobAddSourcesTest(
            $jobSetup,
            $expectedSourcePaths,
            $expectedCompilationEndState,
            $expectedExecutionEndState,
            $expectedApplicationEndState,
            $assertions
        );
    }

    public function createAddSourcesCompileExecuteDataProvider(): array
    {
        return [
            'default' => [
                'jobSetup' => (new JobSetup())
                    ->withCallbackUrl('http://200.example.com/callback/1')
                    ->withManifestPath(getcwd() . '/tests/Fixtures/Manifest/manifest.txt'),
                'expectedSourcePaths' => [
                    'Test/chrome-open-index.yml',
                    'Test/chrome-firefox-open-index.yml',
                    'Test/chrome-open-form.yml',
                ],
                'expectedCompilationEndState' => CompilationState::STATE_COMPLETE,
                'expectedExecutionEndState' => ExecutionState::STATE_COMPLETE,
                'expectedApplicationEndState' => ApplicationState::STATE_COMPLETE,
                'assertions' => TestGetterFactory::assertStates([
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                ]),
            ],
            'verify job is timed out' => [
                'jobSetup' => (new JobSetup())
                    ->withCallbackUrl('http://200.example.com/callback/1')
                    ->withManifestPath(getcwd() . '/tests/Fixtures/Manifest/manifest.txt')
                    ->withMaximumDurationInSeconds(1),
                'expectedSourcePaths' => [
                    'Test/chrome-open-index.yml',
                    'Test/chrome-firefox-open-index.yml',
                    'Test/chrome-open-form.yml',
                ],
                'expectedCompilationEndState' => CompilationState::STATE_COMPLETE,
                'expectedExecutionEndState' => ExecutionState::STATE_CANCELLED,
                'expectedApplicationEndState' => ApplicationState::STATE_TIMED_OUT,
                'assertions' => new InvokableCollection([
                    'verify job and test end states' => new Invokable(
                        function (TestRepository $testRepository) {
                            $tests = $testRepository->findAll();
                            $hasFoundCancelledTest = false;

                            foreach ($tests as $test) {
                                if (Test::STATE_CANCELLED === $test->getState() && false === $hasFoundCancelledTest) {
                                    $hasFoundCancelledTest = true;
                                }

                                if ($hasFoundCancelledTest) {
                                    self::assertSame(Test::STATE_CANCELLED, $test->getState());
                                } else {
                                    self::assertSame(Test::STATE_COMPLETE, $test->getState());
                                }
                            }
                        },
                        [
                            new ServiceReference(TestRepository::class),
                        ]
                    ),
                    'verify last http request type' => new Invokable(
                        function (HttpLogReader $httpLogReader) {
                            // Fixes #676. Wait (0.05 seconds) for the HTTP transaction log to be written to fully.
                            usleep(50000);

                            $httpTransactions = $httpLogReader->getTransactions();
                            $httpLogReader->reset();

                            $lastRequestPayload = [];
                            $lastRequest = $httpTransactions->getRequests()->getLast();
                            if ($lastRequest instanceof RequestInterface) {
                                $lastRequestPayload = json_decode($lastRequest->getBody()->getContents(), true);
                            }

                            self::assertSame(CallbackInterface::TYPE_JOB_TIMEOUT, $lastRequestPayload['type']);
                        },
                        [
                            new ServiceReference(HttpLogReader::class),
                        ]
                    )
                ]),
            ],
        ];
    }
}
