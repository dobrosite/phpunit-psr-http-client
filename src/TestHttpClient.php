<?php

declare(strict_types=1);

namespace DobroSite\PHPUnit\PSR18;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Constraint;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Клиент HTTP для использования в тестах.
 *
 * @since x.x
 */
class TestHttpClient implements ClientInterface
{
    /**
     * @var array<HttpRequestExpectation>
     */
    private array $syncExpectations = [];

    /**
     * @throws AssertionFailedError
     */
    public function assertAllRequestsSent(): void
    {
        if (count($this->syncExpectations) > 0) {
            throw new AssertionFailedError(
                sprintf(
                    "Не были выполнены запросы:\n\t%s",
                    implode(
                        "\n\t",
                        array_map(
                            static function (HttpRequestExpectation $expectation): string {
                                return sprintf(
                                    'method %s, URI %s.',
                                    $expectation->getMethodConstraint()->toString(),
                                    $expectation->getUriConstraint()->toString()
                                );
                            },
                            $this->syncExpectations
                        )
                    )
                )
            );
        }
    }

    public function expectRequest(
        Constraint|string $method,
        Constraint|string $uri
    ): HttpRequestExpectation {
        $expectation = new HttpRequestExpectation($method, $uri);
        $this->syncExpectations[] = $expectation;

        return $expectation;
    }

    /**
     * @throws AssertionFailedError
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $expectation = array_shift($this->syncExpectations);

        if ($expectation === null) {
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
