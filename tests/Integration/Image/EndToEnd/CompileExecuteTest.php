<?php

declare(strict_types=1);

namespace App\Tests\Integration\Image\EndToEnd;

use App\Tests\Integration\AbstractEndToEndTest;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\TestGetterFactory;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\StateBundle\Services\ApplicationState;
use webignition\BasilWorker\StateBundle\Services\CompilationState;
use webignition\BasilWorker\StateBundle\Services\ExecutionState;
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
        $callbackUrl = ($_ENV['CALLBACK_BASE_URL'] ?? '') . '/status/200';

        return [
            'default' => [
                'jobSetup' => (new JobSetup())
                    ->withCallbackUrl($callbackUrl)
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
        ];
    }
}
