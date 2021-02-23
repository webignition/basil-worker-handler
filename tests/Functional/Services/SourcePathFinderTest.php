<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\SourcePathFinder;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\SourceSetup;
use App\Tests\Services\InvokableFactory\SourceSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class SourcePathFinderTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private SourcePathFinder $sourcePathFinder;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider findNextNonCompiledPathDataProvider
     */
    public function testFindNextNonCompiledPath(InvokableInterface $setup, ?string $expectedNextNonCompiledSource): void
    {
        $this->invokableHandler->invoke($setup);

        self::assertSame($expectedNextNonCompiledSource, $this->sourcePathFinder->findNextNonCompiledPath());
    }

    /**
     * @return array[]
     */
    public function findNextNonCompiledPathDataProvider(): array
    {
        $sources = [
            'Test/testZebra.yml',
            'Test/testApple.yml',
            'Test/testBat.yml',
        ];

        return [
            'no job' => [
                'setup' => Invokable::createEmpty(),
                'expectedNextNonCompiledSource' => null,
            ],
            'has job, no sources' => [
                'setup' => JobSetupInvokableFactory::setup(),
                'expectedNextNonCompiledSource' => null,
            ],
            'has job, has sources, no tests' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath($sources[0]),
                        (new SourceSetup())
                            ->withPath($sources[1]),
                        (new SourceSetup())
                            ->withPath($sources[2]),
                    ]),
                ]),
                'expectedNextNonCompiledSource' => $sources[0],
            ],
            'test exists for first source' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath($sources[0]),
                        (new SourceSetup())
                            ->withPath($sources[1]),
                        (new SourceSetup())
                            ->withPath($sources[2]),
                    ]),
                    'create tests' => TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[0]),
                    ]),
                ]),
                'expectedNextNonCompiledSource' => $sources[1],
            ],
            'test exists for first and second sources' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath($sources[0]),
                        (new SourceSetup())
                            ->withPath($sources[1]),
                        (new SourceSetup())
                            ->withPath($sources[2]),
                    ]),
                    'create tests' => TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[0]),
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[1]),
                    ]),
                ]),
                'expectedNextNonCompiledSource' => $sources[2],
            ],
            'tests exist for all sources' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath($sources[0]),
                        (new SourceSetup())
                            ->withPath($sources[1]),
                        (new SourceSetup())
                            ->withPath($sources[2]),
                    ]),
                    'create tests' => TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[0]),
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[1]),
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[2]),
                    ]),
                ]),
                'expectedNextNonCompiledSource' => null,
            ],
        ];
    }

    /**
     * @dataProvider findCompiledPathsDataProvider
     *
     * @param InvokableInterface $setup
     * @param string[] $expectedCompiledSources
     */
    public function testFindCompiledPaths(InvokableInterface $setup, array $expectedCompiledSources): void
    {
        $this->invokableHandler->invoke($setup);

        self::assertSame($expectedCompiledSources, $this->sourcePathFinder->findCompiledPaths());
    }

    /**
     * @return array[]
     */
    public function findCompiledPathsDataProvider(): array
    {
        $sources = [
            'Test/testZebra.yml',
            'Test/testApple.yml',
            'Test/testBat.yml',
        ];

        return [
            'no job' => [
                'setup' => Invokable::createEmpty(),
                'expectedCompiledSources' => [],
            ],
            'has job, no sources' => [
                'setup' => JobSetupInvokableFactory::setup(),
                'expectedCompiledSources' => [],
            ],
            'has job, has sources, no tests' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(),
                ]),
                'expectedCompiledSources' => [],
            ],
            'test exists for first source' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[0]),
                    ]),
                ]),
                'expectedCompiledSources' => [
                    'Test/testZebra.yml',
                ],
            ],
            'test exists for first and second sources' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[0]),
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[1]),
                    ]),
                ]),
                'expectedCompiledSources' => [
                    'Test/testZebra.yml',
                    'Test/testApple.yml',
                ],
            ],
            'tests exist for all sources' => [
                'setup' => new InvokableCollection([
                    JobSetupInvokableFactory::setup(),
                    TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[0]),
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[1]),
                        (new TestSetup())
                            ->withSource('/app/source/' . $sources[2]),
                    ]),
                ]),
                'expectedCompiledSources' => [
                    'Test/testZebra.yml',
                    'Test/testApple.yml',
                    'Test/testBat.yml',
                ],
            ],
        ];
    }
}
