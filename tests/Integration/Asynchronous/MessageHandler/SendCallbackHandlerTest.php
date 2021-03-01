<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous\MessageHandler;

use App\Message\SendCallbackMessage;
use App\Model\BackoffStrategy\ExponentialBackoffStrategy;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\EntityRefresher;
use App\Tests\Services\Integration\HttpLogReader;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\EntityPersister;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\CallbackRepository;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class SendCallbackHandlerTest extends AbstractBaseIntegrationTest
{
    use TestClassServicePropertyInjectorTrait;

    private EntityPersister $entityPersister;
    private HttpLogReader $httpLogReader;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $this->httpLogReader->reset();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->httpLogReader->reset();
    }

    /**
     * @dataProvider sendDataProvider
     */
    public function testSend(
        InvokableInterface $setup,
        CallbackInterface $callback,
        InvokableInterface $waitUntil,
        InvokableInterface $assertions
    ): void {
        $callback->setState(CallbackInterface::STATE_SENDING);
        $this->entityPersister->persist($callback->getEntity());

        $this->invokableHandler->invoke(new InvokableCollection([
            $setup,
            new Invokable(
                function (MessageBusInterface $messageBus, CallbackInterface $callback) {
                    $messageBus->dispatch(new SendCallbackMessage((int) $callback->getId()));
                },
                [
                    new ServiceReference(MessageBusInterface::class),
                    $callback
                ]
            )
        ]));

        $intervalInMicroseconds = 100000;
        while (false === $this->invokableHandler->invoke($waitUntil)) {
            usleep($intervalInMicroseconds);
        }

        $this->invokableHandler->invoke($assertions);
    }

    /**
     * @return array[]
     */
    public function sendDataProvider(): array
    {
        $callbackBaseUrl = $_ENV['CALLBACK_BASE_URL'] ?? '';

        return [
            'success' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withCallbackUrl($callbackBaseUrl . '/status/200')
                    ),
                ]),
                'callback' => CallbackEntity::create(CallbackInterface::TYPE_JOB_TIME_OUT, [
                    'maximum_duration_in_seconds' => 600,
                ]),
                'waitUntil' => $this->createWaitUntilCallbackIsFinished(),
                'assertions' => new Invokable(
                    function (CallbackRepository $callbackRepository) {
                        $callbacks = $callbackRepository->findAll();
                        self::assertCount(1, $callbacks);

                        /** @var CallbackInterface $callback */
                        $callback = $callbacks[0];
                        self::assertInstanceOf(CallbackInterface::class, $callback);

                        self::assertSame(CallbackInterface::STATE_COMPLETE, $callback->getState());
                    },
                    [
                        new ServiceReference(CallbackRepository::class)
                    ]
                ),
            ],
            'verify retried http transactions are delayed' => [
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withCallbackUrl($callbackBaseUrl . '/status/500')
                    ),
                ]),
                'callback' => CallbackEntity::create(CallbackInterface::TYPE_JOB_TIME_OUT, [
                    'maximum_duration_in_seconds' => 600,
                ]),
                'waitUntil' => $this->createWaitUntilCallbackIsFinished(),
                'assertions' => new InvokableCollection([
                    'verify http transactions' => new Invokable(
                        function (HttpLogReader $httpLogReader) {
                            $httpTransactions = $httpLogReader->getTransactions();
                            self::assertCount(3, $httpTransactions);

                            $transactionPeriods = $httpTransactions->getPeriods()->getPeriodsInMicroseconds();
                            self::assertCount(3, $transactionPeriods);

                            $retriedTransactionPeriods = $transactionPeriods;
                            array_shift($retriedTransactionPeriods);

                            $backoffStrategy = new ExponentialBackoffStrategy();
                            foreach ($retriedTransactionPeriods as $retryIndex => $retriedTransactionPeriod) {
                                $retryCount = $retryIndex + 1;
                                $expectedLowerThreshold = $backoffStrategy->getDelay($retryCount) * 1000;
                                $expectedUpperThreshold = $backoffStrategy->getDelay($retryCount + 1) * 1000;

                                self::assertGreaterThanOrEqual($expectedLowerThreshold, $retriedTransactionPeriod);
                                self::assertLessThan($expectedUpperThreshold, $retriedTransactionPeriod);
                            }
                        },
                        [
                            new ServiceReference(HttpLogReader::class),
                        ]
                    )
                ]),
            ],
        ];
    }

    private function createWaitUntilCallbackIsFinished(): InvokableInterface
    {
        return new Invokable(
            function (EntityRefresher $entityRefresher, CallbackRepository $callbackRepository) {
                $entityRefresher->refreshForEntities([
                    CallbackEntity::class,
                ]);

                $callbacks = $callbackRepository->findAll();

                /** @var CallbackInterface $callback */
                $callback = $callbacks[0];

                return in_array($callback->getState(), [
                    CallbackInterface::STATE_COMPLETE,
                    CallbackInterface::STATE_FAILED,
                ]);
            },
            [
                new ServiceReference(EntityRefresher::class),
                new ServiceReference(CallbackRepository::class)
            ]
        );
    }
}
