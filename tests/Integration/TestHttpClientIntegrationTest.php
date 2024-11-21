<?php

declare(strict_types=1);

namespace Tests\Integration;

use DobroSite\PHPUnit\PSR18\TestHttpClientIntegration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DobroSite\PHPUnit\PSR18\TestHttpClientIntegration
 */
final class TestHttpClientIntegrationTest extends TestCase
{
    use TestHttpClientIntegration;

    /**
     * @throws \Throwable
     */
    public function testSingleton(): void
    {
        self::assertSame($this->getHttpClient(), $this->getHttpClient());
    }
}
