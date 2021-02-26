<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\CallbackEventFactory;
use App\Tests\AbstractBaseFunctionalTest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\Yaml\Yaml;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\YamlDocument\Document;

class CallbackEventFactoryTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private CallbackEventFactory $callbackEventFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testCreateSourceCompileFailureEvent(): void
    {
        $source = '/app/source/Test/test.yml';
        $errorOutputData = [
            'key' => 'value',
        ];

        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $errorOutput
            ->shouldReceive('getData')
            ->andReturn($errorOutputData);

        $event = $this->callbackEventFactory->createSourceCompileFailureEvent($source, $errorOutput);

        self::assertSame($source, $event->getSource());
        self::assertSame($errorOutput, $event->getOutput());

        $callback = $event->getCallback();
        self::assertNotNull($callback->getId());
        self::assertSame(CallbackInterface::TYPE_COMPILE_FAILURE, $callback->getType());
        self::assertSame($errorOutputData, $callback->getPayload());
    }

    public function testCreateTestExecuteDocumentReceivedEvent(): void
    {
        $documentData = [
            'key' => 'value',
        ];

        $document = new Document(Yaml::dump($documentData));

        $test = \Mockery::mock(Test::class);

        $event = $this->callbackEventFactory->createTestExecuteDocumentReceivedEvent($test, $document);
        self::assertSame($test, $event->getTest());
        self::assertSame($document, $event->getDocument());

        $callback = $event->getCallback();
        self::assertNotNull($callback->getId());
        self::assertSame(CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED, $callback->getType());
        self::assertSame($documentData, $callback->getPayload());
    }
}
