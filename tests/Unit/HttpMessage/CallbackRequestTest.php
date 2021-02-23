<?php

declare(strict_types=1);

namespace App\Tests\Unit\HttpMessage;

use App\HttpMessage\CallbackRequest;
use App\Tests\Mock\Entity\MockJob;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class CallbackRequestTest extends TestCase
{
    public function testCreate(): void
    {
        $jobCallbackUrl = 'http://example.com/callback';
        $jobLabel = 'label content';

        $job = (new MockJob())
            ->withGetCallbackUrlCall($jobCallbackUrl)
            ->withGetLabelCall($jobLabel)
            ->getMock();

        $callbackType = 'callback type';
        $callbackData = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $callback = \Mockery::mock(CallbackInterface::class);
        $callback
            ->shouldReceive('getType')
            ->andReturn($callbackType);
        $callback
            ->shouldReceive('getPayload')
            ->andReturn($callbackData);

        $request = new CallbackRequest($callback, $job);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame($jobCallbackUrl, (string) $request->getUri());
        self::assertSame('application/json', $request->getHeaderLine('content-type'));
        self::assertSame(
            [
                'label' => $jobLabel,
                'type' => $callbackType,
                'payload' => $callbackData,
            ],
            json_decode((string) $request->getBody(), true)
        );
    }
}
