<?php

declare(strict_types=1);

namespace DobroSite\PHPUnit\PSR18;

use PHPUnit\Framework\AssertionFailedError;

/**
 * Интеграция {@see TestHttpClient} в тесты PHPUnit
 *
 * @since 1.1
 */
trait TestHttpClientIntegration
{
    private ?TestHttpClient $httpClient = null;

    /**
     * @throws AssertionFailedError
     *
     * @after
     */
    public function assertAllExpectedHttpRequestsSent(): void
    {
        $this->getHttpClient()->assertAllRequestsSent();
    }

    protected function getHttpClient(): TestHttpClient
    {
        if (!$this->httpClient instanceof TestHttpClient) {
            $this->httpClient = new TestHttpClient();
        }

        return $this->httpClient;
    }
}
