<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\CallbackFactory;
use App\Tests\AbstractBaseFunctionalTest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\Yaml\Yaml;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\YamlDocument\Document;

class CallbackFactoryTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private CallbackFactory $callbackFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testCreateForCompileFailure(): void
    {
        $errorOutputData = [
            'key1' => 'value1',
            'key2' => [
                'key2key1' => 'key2 value 1',
                'key2key2' => 'key2 value 2',
            ],
        ];

        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $errorOutput
            ->shouldReceive('getData')
            ->andReturn($errorOutputData);

        $callback = $this->callbackFactory->createForCompileFailure($errorOutput);

        self::assertSame(CallbackInterface::TYPE_COMPILE_FAILURE, $callback->getType());
        self::assertSame($errorOutputData, $callback->getPayload());
    }

    public function testCreateForExecuteDocumentReceived(): void
    {
        $documentData = [
            'key1' => 'value1',
            'key2' => [
                'key2key1' => 'key2 value 1',
                'key2key2' => 'key2 value 2',
            ],
        ];

        $document = new Document(Yaml::dump($documentData));
        $callback = $this->callbackFactory->createForExecuteDocumentReceived($document);

        self::assertSame(CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED, $callback->getType());
        self::assertSame($documentData, $callback->getPayload());
    }
}
