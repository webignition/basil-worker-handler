<?php

declare(strict_types=1);

namespace App\Tests\Integration\Synchronous\EndToEnd;

use App\Tests\Integration\AbstractEndToEndTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\Integration\HttpLogReader;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\TestTestRepository;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\StateBundle\Services\ApplicationState;
use webignition\BasilWorker\StateBundle\Services\CompilationState;
use webignition\BasilWorker\StateBundle\Services\ExecutionState;
use webignition\HttpHistoryContainer\Collection\HttpTransactionCollection;
use webignition\HttpHistoryContainer\Collection\HttpTransactionCollectionInterface;
use webignition\HttpHistoryContainer\Transaction\HttpTransaction;
use webignition\HttpHistoryContainer\Transaction\HttpTransactionInterface;

class CompileExecuteTest extends AbstractEndToEndTest
{
    /**
     * @dataProvider createAddSourcesCompileExecuteDataProvider
     *
     * @param string[] $expectedSourcePaths
     * @param CompilationState::STATE_* $expectedCompilationEndState
     * @param ExecutionState::STATE_* $expectedExecutionEndState
     * @param ApplicationState::STATE_* $expectedApplicationEndState
     * @param InvokableInterface $postAssertions
     */
    public function testCreateAddSourcesCompileExecute(
        JobSetup $jobSetup,
        array $expectedSourcePaths,
        string $expectedCompilationEndState,
        string $expectedExecutionEndState,
        string $expectedApplicationEndState,
        InvokableInterface $postAssertions
    ): void {
        $this->doCreateJobAddSourcesTest(
            $jobSetup,
            $expectedSourcePaths,
            $expectedCompilationEndState,
            $expectedExecutionEndState,
            $expectedApplicationEndState,
            $postAssertions
        );
    }

    /**
     * @return array[]
     */
    public function createAddSourcesCompileExecuteDataProvider(): array
    {
        $label = md5('label content');
        $callbackUrl = ($_ENV['CALLBACK_BASE_URL'] ?? '') . '/status/200';

        return [
            'default' => [
                'jobSetup' => (new JobSetup())
                    ->withLabel($label)
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
                'postAssertions' => new Invokable(
                    function (
                        HttpTransactionCollectionInterface $expectedTransactions,
                        HttpLogReader $httpLogReader
                    ) {
                        $transactions = $httpLogReader->getTransactions();
                        $httpLogReader->reset();

                        self::assertCount(count($expectedTransactions), $transactions);
                        $this->assertTransactionCollectionsAreEquivalent($expectedTransactions, $transactions);
                    },
                    [
                        $this->createHttpTransactionCollection([
                            'job/started' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_JOB_STARTED,
                                    []
                                ),
                                new Response()
                            ),
                            'compilation/started: chrome-open-index' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_COMPILATION_STARTED,
                                    [
                                        'source' => 'Test/chrome-open-index.yml',
                                    ]
                                ),
                                new Response()
                            ),
                            'compilation/passed: chrome-open-index' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_COMPILATION_PASSED,
                                    [
                                        'source' => 'Test/chrome-open-index.yml',
                                    ]
                                ),
                                new Response()
                            ),
                            'compilation/started: chrome-firefox-open-index' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_COMPILATION_STARTED,
                                    [
                                        'source' => 'Test/chrome-firefox-open-index.yml',
                                    ]
                                ),
                                new Response()
                            ),
                            'compilation/passed: chrome-firefox-open-index' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_COMPILATION_PASSED,
                                    [
                                        'source' => 'Test/chrome-firefox-open-index.yml',
                                    ]
                                ),
                                new Response()
                            ),
                            'compilation/started: chrome--open-form' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_COMPILATION_STARTED,
                                    [
                                        'source' => 'Test/chrome-open-form.yml',
                                    ]
                                ),
                                new Response()
                            ),
                            'compilation/passed: chrome--open-form' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_COMPILATION_PASSED,
                                    [
                                        'source' => 'Test/chrome-open-form.yml',
                                    ]
                                ),
                                new Response()
                            ),
                            'compilation/completed' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_COMPILATION_SUCCEEDED,
                                    []
                                ),
                                new Response()
                            ),
                            'execution/started' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_EXECUTION_STARTED,
                                    []
                                ),
                                new Response()
                            ),
                            'test/started: chrome-open-index' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_TEST_STARTED,
                                    [
                                        'type' => 'test',
                                        'path' => 'Test/chrome-open-index.yml',
                                        'config' => [
                                            'browser' => 'chrome',
                                            'url' => 'http://nginx-html/index.html',
                                        ],
                                    ]
                                ),
                                new Response()
                            ),
                            'step/passed: chrome-open-index: open' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_STEP_PASSED,
                                    [
                                        'type' => 'step',
                                        'name' => 'verify page is open',
                                        'status' => 'passed',
                                        'statements' => [
                                            [
                                                'type' => 'assertion',
                                                'source' => '$page.url is "http://nginx-html/index.html"',
                                                'status' => 'passed',
                                            ],
                                        ],
                                    ]
                                ),
                                new Response()
                            ),
                            'test/finished: chrome-open-index' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_TEST_FINISHED,
                                    [
                                        'type' => 'test',
                                        'path' => 'Test/chrome-open-index.yml',
                                        'config' => [
                                            'browser' => 'chrome',
                                            'url' => 'http://nginx-html/index.html',
                                        ],
                                    ]
                                ),
                                new Response()
                            ),
                            'test/started: chrome-firefox-open-index: chrome' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_TEST_STARTED,
                                    [
                                        'type' => 'test',
                                        'path' => 'Test/chrome-firefox-open-index.yml',
                                        'config' => [
                                            'browser' => 'chrome',
                                            'url' => 'http://nginx-html/index.html',
                                        ],
                                    ]
                                ),
                                new Response()
                            ),
                            'step/passed: chrome-firefox-open-index: chrome, open' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_STEP_PASSED,
                                    [
                                        'type' => 'step',
                                        'name' => 'verify page is open',
                                        'status' => 'passed',
                                        'statements' => [
                                            [
                                                'type' => 'assertion',
                                                'source' => '$page.url is "http://nginx-html/index.html"',
                                                'status' => 'passed',
                                            ],
                                        ],
                                    ]
                                ),
                                new Response()
                            ),
                            'test/finished: chrome-firefox-open-index: chrome' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_TEST_FINISHED,
                                    [
                                        'type' => 'test',
                                        'path' => 'Test/chrome-firefox-open-index.yml',
                                        'config' => [
                                            'browser' => 'chrome',
                                            'url' => 'http://nginx-html/index.html',
                                        ],
                                    ]
                                ),
                                new Response()
                            ),
                            'test/started: chrome-firefox-open-index: firefox' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_TEST_STARTED,
                                    [
                                        'type' => 'test',
                                        'path' => 'Test/chrome-firefox-open-index.yml',
                                        'config' => [
                                            'browser' => 'firefox',
                                            'url' => 'http://nginx-html/index.html',
                                        ],
                                    ]
                                ),
                                new Response()
                            ),
                            'step/passed: chrome-firefox-open-index: firefox open' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_STEP_PASSED,
                                    [
                                        'type' => 'step',
                                        'name' => 'verify page is open',
                                        'status' => 'passed',
                                        'statements' => [
                                            [
                                                'type' => 'assertion',
                                                'source' => '$page.url is "http://nginx-html/index.html"',
                                                'status' => 'passed',
                                            ],
                                        ],
                                    ]
                                ),
                                new Response()
                            ),
                            'test/finished: chrome-firefox-open-index: firefox' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_TEST_FINISHED,
                                    [
                                        'type' => 'test',
                                        'path' => 'Test/chrome-firefox-open-index.yml',
                                        'config' => [
                                            'browser' => 'firefox',
                                            'url' => 'http://nginx-html/index.html',
                                        ],
                                    ]
                                ),
                                new Response()
                            ),
                            'test/started: chrome-open-form' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_TEST_STARTED,
                                    [
                                        'type' => 'test',
                                        'path' => 'Test/chrome-open-form.yml',
                                        'config' => [
                                            'browser' => 'chrome',
                                            'url' => 'http://nginx-html/form.html',
                                        ],
                                    ]
                                ),
                                new Response()
                            ),
                            'step/passed: chrome-open-form: open' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_STEP_PASSED,
                                    [
                                        'type' => 'step',
                                        'name' => 'verify page is open',
                                        'status' => 'passed',
                                        'statements' => [
                                            [
                                                'type' => 'assertion',
                                                'source' => '$page.url is "http://nginx-html/form.html"',
                                                'status' => 'passed',
                                            ],
                                        ],
                                    ]
                                ),
                                new Response()
                            ),
                            'test/finished: chrome-open-form' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_TEST_FINISHED,
                                    [
                                        'type' => 'test',
                                        'path' => 'Test/chrome-open-form.yml',
                                        'config' => [
                                            'browser' => 'chrome',
                                            'url' => 'http://nginx-html/form.html',
                                        ],
                                    ]
                                ),
                                new Response()
                            ),
                            'job/completed' => $this->createHttpTransaction(
                                $this->createExpectedRequest(
                                    $label,
                                    $callbackUrl,
                                    CallbackInterface::TYPE_JOB_COMPLETED,
                                    []
                                ),
                                new Response()
                            ),
                        ]),
                        new ServiceReference(HttpLogReader::class),
                    ]
                ),
            ],
            'step failed' => [
                'jobSetup' => (new JobSetup())
                    ->withLabel($label)
                    ->withCallbackUrl($callbackUrl)
                    ->withManifestPath(getcwd() . '/tests/Fixtures/Manifest/manifest-step-failure.txt'),
                'expectedSourcePaths' => [
                    'Test/chrome-open-index-with-step-failure.yml',
                    'Test/chrome-open-index.yml',
                ],
                'expectedCompilationEndState' => CompilationState::STATE_COMPLETE,
                'expectedExecutionEndState' => ExecutionState::STATE_CANCELLED,
                'expectedApplicationEndState' => ApplicationState::STATE_COMPLETE,
                'postAssertions' => new InvokableCollection([
                    'verify final http transaction' => new Invokable(
                        function (
                            HttpTransactionCollectionInterface $expectedTransactions,
                            HttpLogReader $httpLogReader
                        ) {
                            $transactions = $httpLogReader->getTransactions();
                            $httpLogReader->reset();

                            $transactions = $transactions->slice(
                                -1 * $expectedTransactions->count(),
                                null
                            );

                            self::assertCount(count($expectedTransactions), $transactions);
                            $this->assertTransactionCollectionsAreEquivalent($expectedTransactions, $transactions);
                        },
                        [
                            $this->createHttpTransactionCollection([
                                'step/failed' => $this->createHttpTransaction(
                                    $this->createExpectedRequest(
                                        $label,
                                        $callbackUrl,
                                        CallbackInterface::TYPE_STEP_FAILED,
                                        [
                                            'type' => 'step',
                                            'name' => 'fail on intentionally-missing element',
                                            'status' => 'failed',
                                            'statements' => [
                                                [
                                                    'type' => 'assertion',
                                                    'source' => '$".non-existent" exists',
                                                    'status' => 'failed',
                                                    'summary' => [
                                                        'operator' => 'exists',
                                                        'source' => [
                                                            'type' => 'node',
                                                            'body' => [
                                                                'type' => 'element',
                                                                'identifier' => [
                                                                    'source' => '$".non-existent"',
                                                                    'properties' => [
                                                                        'type' => 'css',
                                                                        'locator' => '.non-existent',
                                                                        'position' => 1,
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ]
                                    ),
                                    new Response()
                                ),
                                'test/finished' => $this->createHttpTransaction(
                                    $this->createExpectedRequest(
                                        $label,
                                        $callbackUrl,
                                        CallbackInterface::TYPE_TEST_FINISHED,
                                        [
                                            'type' => 'test',
                                            'path' => 'Test/chrome-open-index-with-step-failure.yml',
                                            'config' => [
                                                'browser' => 'chrome',
                                                'url' => 'http://nginx-html/index.html',
                                            ],
                                        ]
                                    ),
                                    new Response()
                                ),
                            ]),
                            new ServiceReference(HttpLogReader::class),
                        ]
                    ),
                    'verify test states' => new Invokable(
                        function (TestTestRepository $testTestRepository) {
                            self::assertSame(
                                $testTestRepository->getStates(),
                                [
                                    Test::STATE_FAILED,
                                    Test::STATE_CANCELLED,
                                ]
                            );
                        },
                        [
                            new ServiceReference(TestTestRepository::class),
                        ]
                    ),
                ]),
            ],
        ];
    }

    private function assertTransactionCollectionsAreEquivalent(
        HttpTransactionCollectionInterface $expectedHttpTransactions,
        HttpTransactionCollectionInterface $transactions
    ): void {
        foreach ($expectedHttpTransactions as $transactionIndex => $expectedTransaction) {
            $transaction = $transactions->get($transactionIndex);
            self::assertInstanceOf(HttpTransactionInterface::class, $transaction);

            $this->assertTransactionsAreEquivalent($expectedTransaction, $transaction, $transactionIndex);
        }
    }

    private function assertTransactionsAreEquivalent(
        HttpTransactionInterface $expected,
        HttpTransactionInterface $actual,
        int $transactionIndex = 0
    ): void {
        $this->assertRequestsAreEquivalent($expected->getRequest(), $actual->getRequest(), $transactionIndex);

        $expectedResponse = $expected->getResponse();
        $actualResponse = $actual->getResponse();

        if (null === $expectedResponse) {
            self::assertNull(
                $actualResponse,
                'Response at index ' . (string) $transactionIndex . 'expected to be null'
            );
        }

        if ($expectedResponse instanceof ResponseInterface) {
            self::assertInstanceOf(ResponseInterface::class, $actualResponse);
            $this->assertResponsesAreEquivalent($expectedResponse, $actualResponse, $transactionIndex);
        }
    }

    private function assertRequestsAreEquivalent(
        RequestInterface $expected,
        RequestInterface $actual,
        int $transactionIndex
    ): void {
        self::assertSame(
            $expected->getMethod(),
            $actual->getMethod(),
            'Method of request at index ' . $transactionIndex . ' not as expected'
        );

        self::assertSame(
            (string) $expected->getUri(),
            (string) $actual->getUri(),
            'URL of request at index ' . $transactionIndex . ' not as expected'
        );

        self::assertSame(
            $expected->getHeaderLine('content-type'),
            $actual->getHeaderLine('content-type'),
            'Content-type header of request at index ' . $transactionIndex . ' not as expected'
        );

        self::assertSame(
            json_decode($expected->getBody()->getContents(), true),
            json_decode($actual->getBody()->getContents(), true),
            'Body of request at index ' . $transactionIndex . ' not as expected'
        );
    }

    private function assertResponsesAreEquivalent(
        ResponseInterface $expected,
        ResponseInterface $actual,
        int $transactionIndex
    ): void {
        self::assertSame(
            $expected->getStatusCode(),
            $actual->getStatusCode(),
            'Status code of response at index ' . $transactionIndex . ' not as expected'
        );
    }

    /**
     * @param HttpTransactionInterface[] $transactions
     *
     * @return HttpTransactionCollection
     */
    private function createHttpTransactionCollection(array $transactions): HttpTransactionCollection
    {
        $collection = new HttpTransactionCollection();
        foreach ($transactions as $transaction) {
            if ($transaction instanceof HttpTransactionInterface) {
                $collection->add($transaction);
            }
        }

        return $collection;
    }

    private function createHttpTransaction(
        RequestInterface $request,
        ResponseInterface $response
    ): HttpTransactionInterface {
        return new HttpTransaction($request, $response, null, []);
    }

    /**
     * @param array<mixed> $payload
     * @param CallbackInterface::TYPE_* $type
     *
     * @return RequestInterface
     */
    private function createExpectedRequest(
        string $label,
        string $callbackUrl,
        string $type,
        array $payload
    ): RequestInterface {
        return new Request(
            'POST',
            $callbackUrl,
            [
                'content-type' => 'application/json',
            ],
            (string) json_encode([
                'label' => $label,
                'type' => $type,
                'payload' => $payload,
            ])
        );
    }
}
