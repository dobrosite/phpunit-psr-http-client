<?php

declare(strict_types=1);

namespace DobroSite\PHPUnit\PSR18;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Constraint;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Клиент HTTP для использования в тестах
 *
 * @since 1.0
 */
class TestHttpClient implements ClientInterface
{
    /**
     * @var array<HttpRequestExpectation>
     */
    private array $syncExpectations = [];

    /**
     * Убеждается, что все ожидаемые запросы действительно были сделаны
     *
     * Этот метод должен вызываться после каждого теста, в котором задавались ожидания запросов по
     * HTTP. Это можно сделать следующими способами.
     *
     *
     *
     * @throws AssertionFailedError
     */
    public function assertAllRequestsSent(): void
    {
        if ($this->syncExpectations !== []) {
            throw new AssertionFailedError(
                sprintf(
                    "Не были выполнены запросы:\n\t%s",
                    implode(
                        "\n\t",
                        array_map(
                            static fn(HttpRequestExpectation $expectation): string => sprintf(
                                'method %s, URI %s.',
                                $expectation->getMethodConstraint()->toString(),
                                $expectation->getUriConstraint()->toString()
                            ),
                            $this->syncExpectations
                        )
                    )
                )
            );
        }
    }

    /**
     * @throws \Throwable
     */
    public function expectRequest(
        Constraint|string $method,
        Constraint|string $uri
    ): HttpRequestExpectation {
        $expectation = new HttpRequestExpectation($method, $uri);
        $this->syncExpectations[] = $expectation;

        return $expectation;
    }

    /**
     * @throws \Throwable
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $expectation = array_shift($this->syncExpectations);

        if (!$expectation instanceof HttpRequestExpectation) {
            throw new AssertionFailedError(
                sprintf(
                    'Неожиданный синхронный запрос "%s %s".',
                    $request->getMethod(),
                    $request->getUri()
                )
            );
        }

        return $expectation->match($request);
    }
}
