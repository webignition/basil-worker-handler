<?php

declare(strict_types=1);

namespace App\Tests\Integration\Synchronous\Services;

use App\Event\TestExecuteDocumentReceivedEvent;
use App\Services\Compiler;
use App\Services\TestExecutor;
use App\Services\TestFactory;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Mock\MockEventDispatcher;
use App\Tests\Model\ExpectedDispatchedEvent;
use App\Tests\Model\ExpectedDispatchedEventCollection;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\ObjectReflector\ObjectReflector;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\TcpCliProxyClient\Client;
use webignition\YamlDocument\Document;

class TestExecutorTest extends AbstractBaseIntegrationTest
{
    use TestClassServicePropertyInjectorTrait;

    private TestExecutor $testExecutor;
    private Compiler $compiler;
    private TestFactory $testFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider executeSuccessDataProvider
     *
     * @param array<int, Document[]> $expectedDispatchedEventDocumentsPerTest
     */
    public function testExecute(string $source, array $expectedDispatchedEventDocumentsPerTest): void
    {
        /** @var SuiteManifest $suiteManifest */
        $suiteManifest = $this->compiler->compile($source);
        self::assertInstanceOf(SuiteManifest::class, $suiteManifest);

        $tests = $this->testFactory->createFromManifestCollection($suiteManifest->getTestManifests());

        $expectedDispatchedEvents = [];
        foreach ($tests as $testIndex => $test) {
            $expectedDispatchedEventDocuments = $expectedDispatchedEventDocumentsPerTest[$testIndex] ?? [];

            foreach ($expectedDispatchedEventDocuments as $expectedDispatchedEventDocument) {
                $expectedDispatchedEvents[] = new ExpectedDispatchedEvent(
                    function (Event $actualEvent) use ($expectedDispatchedEventDocument): bool {
                        self::assertInstanceOf(TestExecuteDocumentReceivedEvent::class, $actualEvent);

                        if ($actualEvent instanceof TestExecuteDocumentReceivedEvent) {
                            self::assertEquals($actualEvent->getDocument(), $expectedDispatchedEventDocument);
                        }

                        return true;
                    }
                );
            }
        }

        $eventDispatcher = (new MockEventDispatcher())
            ->withDispatchCalls(new ExpectedDispatchedEventCollection($expectedDispatchedEvents))
            ->getMock();

        ObjectReflector::setProperty(
            $this->testExecutor,
            TestExecutor::class,
            'eventDispatcher',
            $eventDispatcher
        );

        foreach ($tests as $test) {
            $this->testExecutor->execute($test);
        }
    }

    /**
     * @return array[]
     */
    public function executeSuccessDataProvider(): array
    {
        return [
            'Test/chrome-open-index.yml: single-browser test (chrome)' => [
                'source' => 'Test/chrome-open-index.yml',
                'expectedDispatchedEventDocumentsPerTest' => [
                    [
                        new Document(Yaml::dump(
                            [
                                'type' => 'test',
                                'path' => 'Test/chrome-open-index.yml',
                                'config' => [
                                    'browser' => 'chrome',
                                    'url' => 'http://nginx-html/index.html',
                                ],
                            ],
                            0
                        )),
                        new Document(
                            'type: step' . "\n" .
                            'name: \'verify page is open\'' . "\n" .
                            'status: passed' . "\n" .
                            'statements:' . "\n" .
                            '  -' . "\n" .
                            '    type: assertion' . "\n" .
                            '    source: \'$page.url is "http://nginx-html/index.html"\'' . "\n" .
                            '    status: passed' . "\n"
                        ),
                    ],
                ],
            ],
            'Test/chrome-open-index.yml: single-browser test (firefox)' => [
                'source' => 'Test/firefox-open-index.yml',
                'expectedDispatchedEventDocuments' => [
                    [
                        new Document(Yaml::dump(
                            [
                                'type' => 'test',
                                'path' => 'Test/firefox-open-index.yml',
                                'config' => [
                                    'browser' => 'firefox',
                                    'url' => 'http://nginx-html/index.html',
                                ],
                            ],
                            0
                        )),
                        new Document(
                            'type: step' . "\n" .
                            'name: \'verify page is open\'' . "\n" .
                            'status: passed' . "\n" .
                            'statements:' . "\n" .
                            '  -' . "\n" .
                            '    type: assertion' . "\n" .
                            '    source: \'$page.url is "http://nginx-html/index.html"\'' . "\n" .
                            '    status: passed' . "\n"
                        ),
                    ],
                ],
            ],
            'Test/chrome-firefox-open-index.yml: multi-browser test' => [
                'source' => 'Test/chrome-firefox-open-index.yml',
                'expectedDispatchedEventDocuments' => [
                    [
                        new Document(Yaml::dump(
                            [
                                'type' => 'test',
                                'path' => 'Test/chrome-firefox-open-index.yml',
                                'config' => [
                                    'browser' => 'chrome',
                                    'url' => 'http://nginx-html/index.html',
                                ],
                            ],
                            0
                        )),
                        new Document(
                            'type: step' . "\n" .
                            'name: \'verify page is open\'' . "\n" .
                            'status: passed' . "\n" .
                            'statements:' . "\n" .
                            '  -' . "\n" .
                            '    type: assertion' . "\n" .
                            '    source: \'$page.url is "http://nginx-html/index.html"\'' . "\n" .
                            '    status: passed' . "\n"
                        ),
                    ],
                    [
                        new Document(Yaml::dump(
                            [
                                'type' => 'test',
                                'path' => 'Test/chrome-firefox-open-index.yml',
                                'config' => [
                                    'browser' => 'firefox',
                                    'url' => 'http://nginx-html/index.html',
                                ],
                            ],
                            0
                        )),
                        new Document(
                            'type: step' . "\n" .
                            'name: \'verify page is open\'' . "\n" .
                            'status: passed' . "\n" .
                            'statements:' . "\n" .
                            '  -' . "\n" .
                            '    type: assertion' . "\n" .
                            '    source: \'$page.url is "http://nginx-html/index.html"\'' . "\n" .
                            '    status: passed' . "\n"
                        ),
                    ],
                ],
            ],
        ];
    }

    protected function tearDown(): void
    {
        $compilerClient = self::$container->get('app.services.compiler-client');
        self::assertInstanceOf(Client::class, $compilerClient);

        $compilerTargetDirectory = self::$container->getParameter('compiler_target_directory');
        if (is_string($compilerTargetDirectory)) {
            $request = 'rm ' . $compilerTargetDirectory . '/*.php';
            $compilerClient->request($request);
        }

        parent::tearDown();
    }
}
