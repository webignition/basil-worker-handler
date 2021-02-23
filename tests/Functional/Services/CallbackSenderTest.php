<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\CallbackResponseHandler;
use App\Services\CallbackSender;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockCallbackResponseHandler;
use App\Tests\Model\TestCallback;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Http\Message\ResponseInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\JobFactory;
use webignition\ObjectReflector\ObjectReflector;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CallbackSenderTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private CallbackSender $callbackSender;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $mockHandler = self::$container->get('app.tests.services.guzzle.handler.queuing');
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }
    }

    /**
     * @dataProvider sendResponseSuccessDataProvider
     */
    public function testSendResponseSuccess(ResponseInterface $response): void
    {
        $callback = new TestCallback();
        $callback = $callback->withState(CallbackInterface::STATE_SENDING);

        $this->mockHandler->append($response);

        $responseHandler = (new MockCallbackResponseHandler())
            ->withoutHandleCall()
            ->getMock();

        $this->createJob();
        $this->setCallbackResponseHandlerOnCallbackSender($responseHandler);

        $this->mockHandler->append($response);
        $this->callbackSender->send($callback);

        self::assertSame(CallbackInterface::STATE_COMPLETE, $callback->getState());
    }

    /**
     * @return array[]
     */
    public function sendResponseSuccessDataProvider(): array
    {
        $dataSets = [];

        for ($statusCode = 100; $statusCode < 300; $statusCode++) {
            $dataSets[(string) $statusCode] = [
                'response' => new Response($statusCode),
            ];
        }

        return $dataSets;
    }

    /**
     * @dataProvider sendResponseErrorResponseDataProvider
     */
    public function testSendResponseErrorResponse(object $httpFixture): void
    {
        $callback = new TestCallback();
        $callback = $callback->withState(CallbackInterface::STATE_SENDING);

        $this->mockHandler->append($httpFixture);

        $responseHandler = (new MockCallbackResponseHandler())
            ->withHandleCall($callback, $httpFixture)
            ->getMock();

        $this->createJob();
        $this->setCallbackResponseHandlerOnCallbackSender($responseHandler);

        $this->mockHandler->append($httpFixture);
        $this->callbackSender->send($callback);

        self::assertSame(CallbackInterface::STATE_SENDING, $callback->getState());
    }

    /**
     * @return array[]
     */
    public function sendResponseErrorResponseDataProvider(): array
    {
        return [
            'HTTP 400' => [
                'response' => new Response(400),
            ],
            'Guzzle ConnectException' => [
                'exception' => \Mockery::mock(ConnectException::class),
            ],
        ];
    }

    public function testSendNoJob(): void
    {
        $responseHandler = (new MockCallbackResponseHandler())
            ->withoutHandleCall()
            ->getMock();

        $this->setCallbackResponseHandlerOnCallbackSender($responseHandler);

        $this->callbackSender->send(new TestCallback());
    }

    public function testSendCallbackRetryLimitReached(): void
    {
        $this->createJob();

        $retryLimit = self::$container->getParameter('callback_retry_limit');
        if (!is_numeric($retryLimit)) {
            $retryLimit = 0;
        }

        $retryLimit = (int) $retryLimit;

        $responseHandler = (new MockCallbackResponseHandler())
            ->withoutHandleCall()
            ->getMock();

        $this->setCallbackResponseHandlerOnCallbackSender($responseHandler);

        $callback = new TestCallback();
        $callback = $callback->withRetryCount($retryLimit);
        $callback = $callback->withState(CallbackInterface::STATE_SENDING);

        $this->callbackSender->send($callback);

        self::assertSame(CallbackInterface::STATE_FAILED, $callback->getState());
    }

    private function createJob(): void
    {
        $jobFactory = self::$container->get(JobFactory::class);
        if ($jobFactory instanceof JobFactory) {
            $jobFactory->create('label content', 'http://example.com/callback', 10);
        }
    }

    private function setCallbackResponseHandlerOnCallbackSender(CallbackResponseHandler $responseHandler): void
    {
        ObjectReflector::setProperty(
            $this->callbackSender,
            CallbackSender::class,
            'callbackResponseHandler',
            $responseHandler
        );
    }
}
