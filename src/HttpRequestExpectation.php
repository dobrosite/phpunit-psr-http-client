<?php

declare(strict_types=1);

namespace DobroSite\PHPUnit\PSR18;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\Constraint\JsonMatches;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Ожидание запроса HTTP.
 *
 * @since x.x
 */
class HttpRequestExpectation
{
    private ?Constraint $bodyConstraint = null;

    /**
     * @var array<string, Constraint>
     */
    private array $headersConstraints = [];

    private Constraint $methodConstraint;

    private \Closure $requestResult;

    private Constraint $uriConstraint;

    /**
     * @throws \Throwable
     */
    public function __construct(
        Constraint|string $methodConstraint,
        Constraint|string $uriConstraint
    ) {
        $this->methodConstraint = $methodConstraint instanceof Constraint
            ? $methodConstraint
            : new IsEqual($methodConstraint);

        $this->uriConstraint = $uriConstraint instanceof Constraint
            ? $uriConstraint
            : new IsEqual($uriConstraint);

        $this->willReturn('');
    }

    /**
     * @param Constraint|array<mixed>|string $bodyConstraint
     *
     * @throws \JsonException
     */
    public function body(Constraint|array|string $bodyConstraint): self
    {
        $this->bodyConstraint = match (true) {
            $bodyConstraint instanceof Constraint => $bodyConstraint,
            \is_array($bodyConstraint) => new JsonMatches($this->jsonEncode($bodyConstraint)),
            default => new IsEqual($bodyConstraint),
        };

        return $this;
    }

    public function getMethodConstraint(): Constraint
    {
        return $this->methodConstraint;
    }

    public function getUriConstraint(): Constraint
    {
        return $this->uriConstraint;
    }

    /**
     * @param array<string, mixed> $headers
     *
     * @return $this
     */
    public function headers(array $headers): self
    {
        $this->headersConstraints = \array_map(
            static fn($value) => $value instanceof Constraint ? $value : new IsEqual($value),
            $headers
        );

        return $this;
    }

    /**
     * Проверяет запрос на соответствие ожиданиям и возвращает ответ в случае успеха.
     *
     * @param RequestInterface $request Проверяемый запрос HTTP.
     *
     * @throws \Throwable
     */
    public function match(RequestInterface $request): ResponseInterface
    {
        // Первым проверяем URL, чтобы по ошибке можно было увидеть, если напутан порядок ожиданий.
        Assert::assertThat((string) $request->getUri(), $this->uriConstraint);
        Assert::assertThat($request->getMethod(), $this->methodConstraint);
        foreach ($this->headersConstraints as $header => $constraint) {
            Assert::assertTrue(
                $request->hasHeader($header),
                \sprintf('В запросе отсутствует заголовок "%s".', $header)
            );
            Assert::assertThat($request->getHeaderLine($header), $constraint);
        }
        if ($this->bodyConstraint instanceof Constraint) {
            Assert::assertThat((string) $request->getBody(), $this->bodyConstraint);
        }

        $result = \call_user_func($this->requestResult);
        \assert($result instanceof ResponseInterface);

        return $result;
    }

    /**
     * Задаёт возвращаемый ответ.
     *
     * @param string|array<mixed>|resource|StreamInterface|null $body    Тело ответа.
     * @param array<string, string>                             $headers Заголовки ответа.
     *
     * @throws \Throwable
     */
    public function willReturn(mixed $body, int $statusCode = 200, array $headers = []): self
    {
        if (\is_array($body)) {
            $body = $this->jsonEncode($body);
        }
        $this->requestResult = static fn() => new Response($statusCode, $headers, $body);

        return $this;
    }

    public function willThrowException(\Throwable $exception): self
    {
        $this->requestResult = static function () use ($exception) {
            throw $exception;
        };

        return $this;
    }

    /**
     * @throws \JsonException
     */
    private function jsonEncode(mixed $source): string
    {
        return \json_encode(
            $source,
            \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE
        );
    }
}
