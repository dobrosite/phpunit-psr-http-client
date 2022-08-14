# Инструменты для тестирования кода, использующего PSR-18

Это расширение для [PHPUnit] позволяет в интеграционных и функциональных (прикладных) тестах
задавать ожидания для запросов, выполняемых с помощью клиентов HTTP, совместимых с [PSR-18].

Пример:

```php
$this->getHttpClient()
    ->expectRequest('POST', 'https://example.com/api/v1/users')
    ->headers([
        'accept' => 'application/vnd.api+json',
        'content-type' => 'application/vnd.api+json',
    ])
    ->body([
        'data' => [
            'type' => 'users',
            'attributes' => [
                'email' => 'user@example.com',
            ],
        ],
    ])
    ->willReturn(
        [
            'errors' => [
                [
                    'code' => 'EmailAlreadyRegistered',
                    'title' => 'Адрес электронной почты уже заргистрирован',
                ],
            ],
        ],
        422,
        [
            'content-type' => 'application/vnd.api+json',
        ]
    );
```

## Установка

    composer require dobrosite/phpunit-psr-http-client

## Подключение

В тестовой конфигурации вашего приложения вам надо подменить используемую реализацию
`Psr\Http\Client\ClientInterface` экземпляром `DobroSite\PHPUnit\PSR18\TestHttpClient`. Как это
сделать, зависит от устройства вашего приложения, ниже даны примеры для популярных фреймворков.

### Symfony

В конфигурацию тестового контейнера зависимостей (обычно — `config/services_test.yaml`) добавьте:

```yaml
services:

  Psr\Http\Client\ClientInterface:
    class: DobroSite\PHPUnit\PSR18\TestHttpClient
    public: true
```

Теперь в тесты, унаследованные от `Symfony\Bundle\FrameworkBundle\Test\KernelTestCase`, добавьте
примесь `DobroSite\PHPUnit\PSR18\Symfony\TestHttpClientTrait`:

```php
use DobroSite\PHPUnit\PSR18\Symfony\TestHttpClientTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SomeTest extends WebTestCase
{
    use TestHttpClientTrait;

    public function testSomething(): void
    {
        // Подготовка.
        
        $this->getHttpClient()
            ->expectRequest('GET', 'https://some.service/some/resource')
            ->willReturn([
                'data' => [/* Имитация ответа сторонней службы. */],
            ]);
        
        // Действие.
        
        $client = static::createClient();
        $crawler = $client->request('GET', '/api/foo');

        // Проверки.

        $this->assertResponseIsSuccessful();
    }
}
```

## Как это работает

Предположим, у вас есть класс `Foo`, обращающийся к стороннему API `https://example.com/api`. Для
этого класс пользуется клиентом HTTP, совместимым с PSR-18:

```php
use Psr\Http\Client\ClientInterface;

class Foo 
{
    public function __construct(
        private readonly ClientInterface $httpClient,
    ) {
    }
    
    public function doSomething(): Something
    {
        // …
        $response = $this->httpClient->sendRequest($request);
        // …
    }
}
```

В тестах в конструктор этого класса будет передан экземпляр `TestHttpClient`, который будет
имитировать обмен данными с удалённым сервером без реального выполнения запросов.

## Использование

Перед выполнением проверяемого действия надо настроить `TestHttpClient` — указать, какие запросы
должен выполнить тестируемый код и какие ответы на них надо дать.

### expectRequest

Для каждого ожидаемого запроса надо вызвать метод `TestHttpClient::expectRequest()`, принимающий
два аргумента — $method и $uri — каждый из которых может быть как строкой, так и
экземпляром `PHPUnit\Framework\Constraint\Constraint`. Если проверяемый код сделает запрос HTTP,
метод или URI которого не соответствуют ожиданиям (или вообще не сделает запрос), тест провалится.

```php
$this->getHttpClient()->expectRequest('GET', 'https://some.service/some/resource')
```
Ожидается запрос методом `GET` на URI `https://some.service/some/resource`.

```php
$this->getHttpClient()->expectRequest(new IsAnything(), new StringStartsWith('https://example.com/'))
// или
$this->getHttpClient()->expectRequest(self::anything(), self::stringStartsWith('https://example.com/'))
```
Ожидается запрос любим методом на URI начинающийся с `https://example.com/`.

### headers

Метод `headers` позволяет задать ожидания для заголовков запроса, который должен выполнить
проверяемый код. Единственный аргумент метода должен содержать ассоциативный массив, ключи которого
— имена заголовков (регистр не имеет значения), а значения — ожидаемые значения этих заголовков.
В качестве значений можно использовать как строки, таки экземпляры `Constraint`.

```php
$this->getHttpClient()->expectRequest(/* … */)->headers([
    'Accept' => self::stringContains('application/json'),
    'content-type' => 'application/json',
]);
```

### body

Метод `body` задаёт ожидание для тела запроса. В аргументе `$bodyConstraint` метод может принимать:

- строку — будет интерпретирована как `new IsEqual($bodyConstraint)`;
- массив — будет интерпретирован как `new JsonMatches(json_encode($bodyConstraint))`;
- экземпляр `Constraint`.

```php
$this->getHttpClient()->expectRequest(/* … */)->body('{"foo":"bar"}');
$this->getHttpClient()->expectRequest(/* … */)->body(['foo' => 'bar']);
$this->getHttpClient()->expectRequest(/* … */)->body(self::stringContains('bar'));
```

### willReturn

Метод `willReturn` позволяет задать ответ, который получит проверяемый код в ответ на ожидаемый
запрос. У метода 3 аргумента.

- `$body` — тело ответа. Может быть:
  - экземпляром `StreamInterface` — будет возвращено как есть;
  - строкой или ресурсом — будет преобразовано к `StreamInterface`;
  - массивом — будет преобразовано в JSON;
  - `null` — в ответе не будет тела.
- `$statusCode` — код состояния HTTP;
- `$headers` — заголовки ответа. Ассоциативный массив «заголовок => значение». Регистр имён не имеет
  значения.

```php
$this->getHttpClient()->expectRequest(/* … */)->willReturn(
    body: '{"foo":"bar"}',
    statusCode: 200,
);
$this->getHttpClient()->expectRequest(/* … */)->willReturn(
    body: null,
    statusCode: 204,
    headers: ['ETag' => 'viChieyiupaidahng6eiv3bohRohcohb']
);
```

### willThrowException

Выбросит переданное методу исключение.

```php
$this->getHttpClient()->expectRequest(/* … */)->willThrowException(new SomeException(/* … */));
```

[PHPUnit]: https://phpunit.de/
[PSR-18]: https://php-fig.org/psr/psr-18/
