<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Services\SourceCompileEventDispatcher;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockEventDispatcher;
use App\Tests\Model\ExpectedDispatchedEvent;
use App\Tests\Model\ExpectedDispatchedEventCollection;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilCompilerModels\OutputInterface;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\ObjectReflector\ObjectReflector;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class SourceCompileEventDispatcherTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private SourceCompileEventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testDispatchEventNotDispatched(): void
    {
        $source = 'Test/test1.yml';
        $output = \Mockery::mock(OutputInterface::class);

        $this->dispatcher->dispatch($source, $output);

        $eventDispatcher = (new MockEventDispatcher())
            ->withoutDispatchCall()
            ->getMock();

        $this->setSourceCompileEventDispatcherEventDispatcher($eventDispatcher);

        $this->dispatcher->dispatch($source, $output);
    }

    /**
     * @dataProvider dispatchEventDispatchedDataProvider
     */
    public function testDispatchEventDispatched(
        string $source,
        OutputInterface $output,
        ExpectedDispatchedEvent $expectedDispatchedEvent
    ): void {
        $eventDispatcher = (new MockEventDispatcher())
            ->withDispatchCalls(new ExpectedDispatchedEventCollection([
                $expectedDispatchedEvent,
            ]))
            ->getMock();

        $this->setSourceCompileEventDispatcherEventDispatcher($eventDispatcher);

        $this->dispatcher->dispatch($source, $output);
    }

    /**
     * @return array[]
     */
    public function dispatchEventDispatchedDataProvider(): array
    {
        $compileFailureErrorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $compileFailureErrorOutput
            ->shouldReceive('getData')
            ->andReturn([
                'foo' => 'bar',
            ]);

        $successOutput = \Mockery::mock(SuiteManifest::class);

        return [
            'error output' => [
                'source' => 'Test/test1.yml',
                'output' => $compileFailureErrorOutput,
                'expectedDispatchedEvent' => new ExpectedDispatchedEvent(
                    function (Event $actualEvent) use ($compileFailureErrorOutput) {
                        self::assertInstanceOf(SourceCompileFailureEvent::class, $actualEvent);

                        if ($actualEvent instanceof SourceCompileFailureEvent) {
                            self::assertSame('Test/test1.yml', $actualEvent->getSource());
                            self::assertSame($actualEvent->getOutput(), $compileFailureErrorOutput);
                        }

                        return true;
                    },
                ),
            ],
            'success output' => [
                'source' => 'Test/test2.yml',
                'output' => $successOutput,
                'expectedDispatchedEvent' => ExpectedDispatchedEvent::createAssertEquals(
                    new SourceCompileSuccessEvent('Test/test2.yml', $successOutput)
                ),
                'expectedEvent' => new SourceCompileSuccessEvent('Test/test2.yml', $successOutput),
            ],
        ];
    }

    private function setSourceCompileEventDispatcherEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        ObjectReflector::setProperty(
            $this->dispatcher,
            SourceCompileEventDispatcher::class,
            'dispatcher',
            $eventDispatcher
        );
    }
}
