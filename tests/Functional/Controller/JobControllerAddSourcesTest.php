<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Event\JobReadyEvent;
use App\Services\SourceFileStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\InvokableFactory\SourceGetterFactory;
use App\Tests\Services\InvokableFactory\SourcesAddedEventGetter;
use App\Tests\Services\InvokableHandler;
use App\Tests\Services\SourceFileStoreInitializer;
use App\Tests\Services\UploadedFileFactory;
use Symfony\Component\HttpFoundation\Response;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\JobFactory;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class JobControllerAddSourcesTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private const EXPECTED_SOURCES = [
        'Test/chrome-open-index.yml',
        'Test/chrome-firefox-open-index.yml',
        'Test/chrome-open-form.yml',
    ];

    private Response $response;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
        $this->initializeSourceFileStore();

        $jobFactory = self::$container->get(JobFactory::class);
        self::assertInstanceOf(JobFactory::class, $jobFactory);

        $jobFactory->create(md5('label content'), 'http://example.com/callback', 10);

        self::assertSame([], $this->invokableHandler->invoke(SourceGetterFactory::getAll()));
        self::assertNull($this->invokableHandler->invoke(SourcesAddedEventGetter::get()));

        $this->response = $this->invokableHandler->invoke(new Invokable(
            function (
                ClientRequestSender $clientRequestSender,
                UploadedFileFactory $uploadedFileFactory,
                BasilFixtureHandler $basilFixtureHandler
            ) {
                return $clientRequestSender->addJobSources(
                    $uploadedFileFactory->createForManifest(getcwd() . '/tests/Fixtures/Manifest/manifest.txt'),
                    $basilFixtureHandler->createUploadFileCollection(self::EXPECTED_SOURCES)
                );
            },
            [
                new ServiceReference(ClientRequestSender::class),
                new ServiceReference(UploadedFileFactory::class),
                new ServiceReference(BasilFixtureHandler::class)
            ]
        ));
    }

    public function testResponse()
    {
        self::assertSame(200, $this->response->getStatusCode());
        self::assertSame('application/json', $this->response->headers->get('content-type'));
        self::assertSame('{}', $this->response->getContent());
    }

    public function testSourcesAreCreated()
    {
        self::assertSame(
            self::EXPECTED_SOURCES,
            $this->invokableHandler->invoke(SourceGetterFactory::getAllRelativePaths())
        );
    }

    public function testSourcesAreStored()
    {
        foreach (self::EXPECTED_SOURCES as $expectedSource) {
            self::assertTrue(
                $this->invokableHandler->invoke(new Invokable(
                    function (SourceFileStore $sourceFileStore, string $expectedSource) {
                        return $sourceFileStore->has($expectedSource);
                    },
                    [
                        new ServiceReference(SourceFileStore::class),
                        $expectedSource
                    ]
                ))
            );
        }
    }

    public function testSourcesAddedEventIsDispatched()
    {
        self::assertEquals(
            new JobReadyEvent(),
            $this->invokableHandler->invoke(SourcesAddedEventGetter::get())
        );
    }

    private function initializeSourceFileStore(): void
    {
        $this->invokableHandler->invoke(new Invokable(
            function (SourceFileStoreInitializer $sourceFileStoreInitializer) {
                $sourceFileStoreInitializer->initialize();
            },
            [
                new ServiceReference(SourceFileStoreInitializer::class),
            ]
        ));
    }
}
