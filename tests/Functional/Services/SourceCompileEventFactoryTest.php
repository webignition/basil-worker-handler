<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use App\Model\Callback\CompileFailureCallback;
use App\Services\SourceCompileEventFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockSuiteManifest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilCompilerModels\OutputInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class SourceCompileEventFactoryTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private SourceCompileEventFactory $sourceCompileEventFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider createForSourceCompileFailureDataProvider
     */
    public function testCreateForSourceCompileFailure(string $source, OutputInterface $output): void
    {
        $event = $this->sourceCompileEventFactory->create($source, $output);

        self::assertInstanceOf(SourceCompileFailureEvent::class, $event);
        self::assertSame($source, $event->getSource());
        self::assertSame($output, $event->getOutput());

        if ($event instanceof SourceCompileFailureEvent) {
            $callback = $event->getCallback();
            self::assertSame($output->getData(), $callback->getPayload());
            self::assertIsInt($callback->getId());
        }
    }

    /**
     * @return array[]
     */
    public function createForSourceCompileFailureDataProvider(): array
    {
        $source = '/app/source/Test/test.yml';
        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $errorOutput
            ->shouldReceive('getData')
            ->andReturn([
                'foo' => 'bar',
            ]);

        return [
            'default' => [
                'source' => '/app/source/Test/test.yml',
                'output' => $errorOutput,
                'expectedEvent' => new SourceCompileFailureEvent(
                    $source,
                    $errorOutput,
                    new CompileFailureCallback($errorOutput)
                ),
            ],
        ];
    }

    public function testCreateForSourceCompileSuccess(): void
    {
        $source = '/app/source/Test/test.yml';
        $output = (new MockSuiteManifest())->getMock();
        $expectedEvent = new SourceCompileSuccessEvent($source, $output);

        self::assertEquals($expectedEvent, $this->sourceCompileEventFactory->create($source, $output));
    }
}
