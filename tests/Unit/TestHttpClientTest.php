<?php

declare(strict_types=1);

namespace Tests\Unit;

use DobroSite\PHPUnit\PSR18\TestHttpClient;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestHttpClient::class)]
final class TestHttpClientTest extends TestCase
{
    /**
     * @throws \Throwable
     */
    public function testSyncRequestNotMatched(): void
    {
        $httpClient = new TestHttpClient();
        $httpClient->expectRequest('GET', 'https://example.com');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that two strings are equal.');

        $httpClient->sendRequest(new Request('GET', '/'));
    }

    /**
     * @throws \Throwable
     */
    public function testSyncRequestNotSent(): void
    {
        $httpClient = new TestHttpClient();
        $httpClient->expectRequest('GET', 'https://example.com');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            "Не были выполнены запросы:\n\tmethod is equal to 'GET', URI is equal to 'https://example.com'."
        );

        $httpClient->assertAllRequestsSent();
    }

    /**
     * @throws \Throwable
     */
    public function testUnexpectedSyncRequest(): void
    {
        $httpClient = new TestHttpClient();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Неожиданный синхронный запрос "GET /".');

        $httpClient->sendRequest(new Request('GET', '/'));
    }
}
