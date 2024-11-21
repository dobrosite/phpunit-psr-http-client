<?php

declare(strict_types=1);

namespace DobroSite\PHPUnit\PSR18\Symfony;

use DobroSite\PHPUnit\PSR18\TestHttpClient;
use PHPUnit\Framework\AssertionFailedError;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

trait TestHttpClientTrait
{
    /**
     * @throws AssertionFailedError
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     *
     * @after
     */
    public function assertAllExpectedHttpRequestsSent(): void
    {
        $this->getHttpClient()->assertAllRequestsSent();
    }

    abstract protected static function getContainer(): ContainerInterface;

    /**
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    protected function getHttpClient(): TestHttpClient
    {
        $client = self::getContainer()->get(ClientInterface::class);
        \assert($client instanceof TestHttpClient);

        return $client;
    }
}
