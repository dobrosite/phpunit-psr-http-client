<?php

declare(strict_types=1);

namespace Tests\Unit;

use DobroSite\PHPUnit\PSR18\HttpRequestExpectation;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\TestCase;

#[CoversClass(HttpRequestExpectation::class)]
final class HttpRequestExpectationTest extends TestCase
{
    /**
     * Поставщик данных для {@see testDifferentRequestBodyTypes}.
     *
     * @return iterable<string, array<mixed>>
     *
     * @throws \Throwable
     */
    public static function requestBodyDataProvider(): iterable
    {
        $resource = fopen('php://memory', 'r+b');
        if ($resource) {
            fwrite($resource, 'Ресурс');
        } else {
            self::fail('Не удалось создать ресурс "php://memory".');
        }

        return [
            'Строка' => [
                'requestBodyExpectation' => 'Строка',
                'receivedRequestBody' => 'Строка',
            ],
            'Constraint' => [
                'requestBodyExpectation' => new IsEqual('Строка'),
                'receivedRequestBody' => 'Строка',
            ],
            'Массив' => [
                'requestBodyExpectation' => ['foo' => 'bar'],
                'receivedRequestBody' => '{"foo": "bar"}',
            ],
        ];
    }

    /**
     * Поставщик данных для {@see testDifferentResponseBodyTypes}
     *
     * @return iterable<string, array<mixed>>
     *
     * @throws \Throwable
     */
    public static function responseBodyDataProvider(): iterable
    {
        $resource = fopen('php://memory', 'r+b');
        if ($resource) {
            fwrite($resource, 'Ресурс');
        } else {
            self::fail('Не удалось создать ресурс "php://memory".');
        }

        return [
            'Строка' => [
                'responseBodyToReturn' => 'Строка',
                'expectedBody' => 'Строка',
            ],
            'Ресурс' => [
                'responseBodyToReturn' => $resource,
                'expectedBody' => 'Ресурс',
            ],
            'Stream' => [
                'responseBodyToReturn' => Stream::create('Stream'),
                'expectedBody' => 'Stream',
            ],
            'null' => [
                'responseBodyToReturn' => null,
                'expectedBody' => '',
            ],
            'Массив' => [
                'responseBodyToReturn' => ['foo' => 'bar'],
                'expectedBody' => '{"foo":"bar"}',
            ],
        ];
    }

    /**
     * @throws \Throwable
     */
    #[DataProvider('requestBodyDataProvider')]
    public function testDifferentRequestBodyTypes(
        mixed $requestBodyExpectation,
        mixed $receivedRequestBody
    ): void {
        $exp = new HttpRequestExpectation('GET', '/');
        $exp->body($requestBodyExpectation);

        $exp->match(new Request('GET', '/', [], $receivedRequestBody));
    }

    /**
     * @throws \Throwable
     */
    #[DataProvider('responseBodyDataProvider')]
    public function testDifferentResponseBodyTypes(
        mixed $responseBodyToReturn,
        string $expectedBody
    ): void {
        $exp = new HttpRequestExpectation('GET', '/');
        $exp->willReturn($responseBodyToReturn);

        $response = $exp->match(new Request('GET', '/'));

        self::assertEquals($expectedBody, (string) $response->getBody());
    }

    /**
     * @throws \Throwable
     */
    public function testGetExpectationText(): void
    {
        $expectation = new HttpRequestExpectation('GET', 'https://example.com');

        self::assertEquals(
            "is equal to 'GET'",
            $expectation->getMethodConstraint()->toString()
        );
        self::assertEquals(
            "is equal to 'https://example.com'",
            $expectation->getUriConstraint()->toString()
        );
    }

    /**
     * @throws \Throwable
     */
    public function testReturnExpectedResponse(): void
    {
        $exp = new HttpRequestExpectation('GET', '/foo');
        $exp->willReturn('{ТЕЛО}', 123, ['Foo' => 'Bar']);

        $response = $exp->match(new Request('GET', '/foo'));

        self::assertEquals(123, $response->getStatusCode());
        self::assertEquals('{ТЕЛО}', (string) $response->getBody());
        self::assertEquals('Bar', $response->getHeaderLine('Foo'));
    }

    /**
     * @throws \Throwable
     */
    public function testThrowExpectedException(): void
    {
        $exp = new HttpRequestExpectation('GET', '/foo');
        $exp->willThrowException(new \OutOfBoundsException('Текст.'));

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Текст.');

        $exp->match(new Request('GET', '/foo'));
    }
}
