<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Message\CompileSourceMessage;
use App\MessageHandler\CompileSourceHandler;
use App\Services\SourceCompileEventDispatcher;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockSuiteManifest;
use App\Tests\Mock\Services\MockCompiler;
use App\Tests\Mock\Services\MockSourceCompileEventDispatcher;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\SourceSetup;
use App\Tests\Services\InvokableFactory\SourceSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilCompilerModels\TestManifest;
use webignition\ObjectReflector\ObjectReflector;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CompileSourceHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private CompileSourceHandler $handler;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testInvokeNoJob()
    {
        $eventDispatcher = (new MockSourceCompileEventDispatcher())
            ->withoutDispatchCall()
            ->getMock();

        ObjectReflector::setProperty($this->handler, CompileSourceHandler::class, 'eventDispatcher', $eventDispatcher);

        $handler = $this->handler;
        $handler(\Mockery::mock(CompileSourceMessage::class));
    }

    public function testInvokeJobInWrongState()
    {
        $this->invokableHandler->invoke(JobSetupInvokableFactory::setup());

        $eventDispatcher = (new MockSourceCompileEventDispatcher())
            ->withoutDispatchCall()
            ->getMock();

        ObjectReflector::setProperty($this->handler, CompileSourceHandler::class, 'eventDispatcher', $eventDispatcher);

        $handler = $this->handler;
        $handler(\Mockery::mock(CompileSourceMessage::class));
    }

    public function testInvokeCompileSuccess()
    {
        $sourcePath = 'Test/test1.yml';

        $this->invokableHandler->invoke(new InvokableCollection([
            'create job' => JobSetupInvokableFactory::setup(),
            'add job sources' => SourceSetupInvokableFactory::setupCollection([
                (new SourceSetup())
                    ->withPath($sourcePath),
            ]),
        ]));

        $compileSourceMessage = new CompileSourceMessage($sourcePath);

        $testManifests = [
            \Mockery::mock(TestManifest::class),
            \Mockery::mock(TestManifest::class),
        ];

        $suiteManifest = (new MockSuiteManifest())
            ->withGetTestManifestsCall($testManifests)
            ->getMock();

        $compiler = (new MockCompiler())
            ->withCompileCall(
                $compileSourceMessage->getPath(),
                $suiteManifest
            )
            ->getMock();

        ObjectReflector::setProperty(
            $this->handler,
            CompileSourceHandler::class,
            'compiler',
            $compiler
        );

        $eventDispatcher = (new MockSourceCompileEventDispatcher())
            ->withDispatchCall($sourcePath, $suiteManifest)
            ->getMock();

        $this->setCompileSourceHandlerEventDispatcher($eventDispatcher);

        $handler = $this->handler;
        $handler($compileSourceMessage);
    }

    public function testInvokeCompileFailure()
    {
        $sourcePath = 'Test/test1.yml';

        $this->invokableHandler->invoke(new InvokableCollection([
            'create job' => JobSetupInvokableFactory::setup(),
            'add job sources' => SourceSetupInvokableFactory::setupCollection([
                (new SourceSetup())
                    ->withPath($sourcePath),
            ]),
        ]));

        $compileSourceMessage = new CompileSourceMessage($sourcePath);
        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);

        $compiler = (new MockCompiler())
            ->withCompileCall(
                $compileSourceMessage->getPath(),
                $errorOutput
            )
            ->getMock();

        ObjectReflector::setProperty(
            $this->handler,
            CompileSourceHandler::class,
            'compiler',
            $compiler
        );

        $eventDispatcher = (new MockSourceCompileEventDispatcher())
            ->withDispatchCall($sourcePath, $errorOutput)
            ->getMock();

        $this->setCompileSourceHandlerEventDispatcher($eventDispatcher);

        $handler = $this->handler;
        $handler($compileSourceMessage);
    }

    private function setCompileSourceHandlerEventDispatcher(SourceCompileEventDispatcher $eventDispatcher): void
    {
        ObjectReflector::setProperty($this->handler, CompileSourceHandler::class, 'eventDispatcher', $eventDispatcher);
    }
}
