<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Unit\Http;

use Composer\InstalledVersions;
use GuzzleHttp\Exception\ConnectException;
use Hihaho\Sdk\Exceptions\AiTaskRefusedException;
use Hihaho\Sdk\Exceptions\AuthenticationException;
use Hihaho\Sdk\Exceptions\AuthorizationException;
use Hihaho\Sdk\Exceptions\HttpException;
use Hihaho\Sdk\Exceptions\InvalidResponseException;
use Hihaho\Sdk\Exceptions\NotFoundException;
use Hihaho\Sdk\Exceptions\PreconditionFailedException;
use Hihaho\Sdk\Exceptions\RateLimitedException;
use Hihaho\Sdk\Exceptions\ServerException;
use Hihaho\Sdk\Exceptions\TransportException;
use Hihaho\Sdk\Exceptions\UnexpectedStatusException;
use Hihaho\Sdk\Exceptions\ValidationException;
use Hihaho\Sdk\Hihaho;
use Hihaho\Sdk\Http\Connector;
use Hihaho\Sdk\Http\RetryPolicy;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConnectorTest extends TestCase
{
    private Factory $http;

    protected function setUp(): void
    {
        parent::setUp();

        $this->http = new Factory();
        Sleep::fake();
    }

    protected function tearDown(): void
    {
        Sleep::fake(false);

        parent::tearDown();
    }

    #[Test]
    public function it_sends_the_token_and_json_accept_header_to_the_api_host(): void
    {
        $this->http->fake(['api.hihaho.com/v2/video*' => Factory::response(['data' => []])]);

        $this->connector()->get('v2/video', ['q' => 'safety']);

        $this->http->assertSent(fn (Request $request): bool => $request->url() === 'https://api.hihaho.com/v2/video?q=safety'
            && $request->hasHeader('Authorization', 'Bearer secret-token')
            && $request->hasHeader('Accept', 'application/json'));
    }

    #[Test]
    public function it_sends_the_sdk_and_php_version_as_user_agent(): void
    {
        $this->http->fake(['*' => Factory::response(['data' => []])]);

        $this->connector()->get('v2/video');

        $userAgent = 'hihaho-php/' . InstalledVersions::getPrettyVersion('hihaho/hihaho-php') . ' PHP/' . PHP_VERSION;
        $this->http->assertSent(fn (Request $request): bool => $request->hasHeader('User-Agent', $userAgent));
    }

    #[Test]
    public function it_works_without_a_booted_laravel_container(): void
    {
        $connector = new Connector(http: $this->http, token: 'secret-token');
        $this->http->fake(['*' => Factory::response(['status' => 'ok'])]);

        $response = $connector->get('status');

        $this->assertSame(['status' => 'ok'], $connector->decode($response));
    }

    #[Test]
    public function a_dump_of_the_client_does_not_show_the_token(): void
    {
        $dump = print_r(new Hihaho(token: 'secret-token', http: $this->http), true);

        $this->assertStringNotContainsString('secret-token', $dump);
        $this->assertStringContainsString('[redacted]', $dump);
    }

    #[Test]
    #[DataProvider('insecureBaseUrlProvider')]
    public function a_base_url_without_https_is_rejected(string $baseUrl): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Connector(http: $this->http, token: 'secret-token', baseUrl: $baseUrl);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function insecureBaseUrlProvider(): iterable
    {
        yield 'http host' => ['http://api.hihaho.com'];
        yield 'http host that starts with localhost' => ['http://localhost.example.com'];
        yield 'no scheme' => ['api.hihaho.com'];
    }

    #[Test]
    public function an_empty_token_throws_before_anything_is_sent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HIHAHO_API_TOKEN');

        new Hihaho(token: ' ', http: $this->http);
    }

    #[Test]
    #[DataProvider('allowedBaseUrlProvider')]
    public function an_https_or_local_base_url_is_accepted(string $baseUrl): void
    {
        $this->http->fake(['*' => Factory::response(['data' => []])]);

        new Connector(http: $this->http, token: 'secret-token', baseUrl: $baseUrl)->get('v2/video');

        $this->http->assertSentCount(1);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function allowedBaseUrlProvider(): iterable
    {
        yield 'https' => ['https://api.hihaho.com'];
        yield 'upper case https' => ['HTTPS://api.hihaho.com'];
        yield 'localhost' => ['http://localhost:8000'];
        yield 'loopback ip' => ['http://127.0.0.1'];
    }

    /**
     * @param  array<string, mixed>|string  $body
     * @param  class-string<HttpException>  $exception
     */
    #[Test]
    #[DataProvider('statusProvider')]
    public function it_maps_each_status_to_its_exception(int $status, array|string $body, string $exception): void
    {
        $this->http->fake(['*' => Factory::response($body, $status)]);

        try {
            $this->connector(RetryPolicy::disabled())->post('v2/video/1/ai-task');
            self::fail('No exception was thrown.');
        } catch (HttpException $httpException) {
            $this->assertSame($httpException::class, $exception);
            $this->assertSame($status, $httpException->status());
        }
    }

    /**
     * @return iterable<string, array{int, array<string, mixed>|string, class-string<HttpException>}>
     */
    public static function statusProvider(): iterable
    {
        yield 'redirect to the flagged page' => [302, '', AuthenticationException::class];
        yield 'unauthenticated' => [401, ['message' => 'Unauthenticated.'], AuthenticationException::class];
        yield 'forbidden' => [403, ['message' => 'No.', 'success' => false], AuthorizationException::class];
        yield 'not found with an empty body' => [404, '', NotFoundException::class];
        yield 'precondition failed' => [412, ['message' => 'Off.'], PreconditionFailedException::class];
        yield 'validation' => [422, ['message' => 'Invalid.', 'errors' => ['0.type' => ['Required.']]], ValidationException::class];
        yield 'rate limited' => [429, ['message' => 'Too Many Attempts.'], RateLimitedException::class];
        yield 'ai task refused' => [400, ['message' => 'Busy.', 'tasks' => []], AiTaskRefusedException::class];
        yield 'other 400' => [400, ['message' => 'Bad.'], UnexpectedStatusException::class];
        yield 'unlisted status' => [409, ['message' => 'Conflict.'], UnexpectedStatusException::class];
        yield 'server error' => [500, ['message' => 'Server Error'], ServerException::class];
    }

    #[Test]
    public function an_http_exception_keeps_the_status_body_and_headers(): void
    {
        $this->http->fake(['*' => Factory::response(['message' => 'Invalid.', 'errors' => ['name' => ['Required.']]], 422, ['X-Request-Id' => 'abc'])]);

        try {
            $this->connector(RetryPolicy::disabled())->post('v2/video');
            self::fail('No exception was thrown.');
        } catch (ValidationException $validationException) {
            $this->assertSame(422, $validationException->status());
            $this->assertSame(['message' => 'Invalid.', 'errors' => ['name' => ['Required.']]], $validationException->body());
            $this->assertSame(['abc'], $validationException->headers()['X-Request-Id'] ?? null);
            $this->assertSame(['name' => ['Required.']], $validationException->errors());
        }
    }

    #[Test]
    #[DataProvider('leakProvider')]
    public function an_exception_does_not_hold_the_bearer_token(int $status, string $body, bool $serializable): void
    {
        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');
        // A local factory: the test case is in the trace, and a factory on it records the request.
        $http = new Factory();
        $http->fake(['*' => Factory::response($body, $status)]);

        $hihaho = new Hihaho(token: 'secret-token', http: $http, retryPolicy: RetryPolicy::disabled());

        try {
            $hihaho->video(1)->get();
            self::fail('No exception was thrown.');
        } catch (HttpException $httpException) {
            $exported = var_export($httpException, true);
            $this->assertStringNotContainsString('Bearer', $exported);
            $this->assertStringNotContainsString('secret-token', $exported);

            if ($serializable) {
                $this->assertStringNotContainsString('Bearer', serialize($httpException));
            }
        } finally {
            ini_set('zend.exception_ignore_args', (string) $ignoreArgs);
        }
    }

    /**
     * @return iterable<string, array{int, string, bool}>
     */
    public static function leakProvider(): iterable
    {
        yield 'error status' => [404, '{"message":"Not found."}', true];
        yield 'success without data' => [200, '{"meta":[]}', false];
    }

    #[Test]
    public function a_transport_exception_does_not_hold_the_bearer_token(): void
    {
        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');
        $http = new Factory();
        $http->fake(fn (Request $request) => throw new ConnectException('Timed out', $request->toPsrRequest()));

        $hihaho = new Hihaho(token: 'secret-token', http: $http, retryPolicy: RetryPolicy::disabled());

        try {
            $hihaho->video(1)->get();
            self::fail('No exception was thrown.');
        } catch (TransportException $transportException) {
            $exported = var_export($transportException, true);
            $this->assertStringNotContainsString('Bearer', $exported);
            $this->assertStringNotContainsString('secret-token', $exported);
            $this->assertStringContainsString('Timed out', $transportException->getMessage());
        } finally {
            ini_set('zend.exception_ignore_args', (string) $ignoreArgs);
        }
    }

    #[Test]
    public function a_connection_failure_throws_a_transport_exception(): void
    {
        $this->http->fake(fn () => throw new ConnectionException('Timed out'));

        $this->expectException(TransportException::class);

        $this->connector(RetryPolicy::disabled())->get('v2/video');
    }

    #[Test]
    public function a_get_is_retried_after_a_connection_failure(): void
    {
        $attempts = 0;
        $this->http->fake(function () use (&$attempts) {
            ++$attempts;

            if ($attempts === 1) {
                throw new ConnectionException('Timed out');
            }

            return Factory::response(['data' => []]);
        });

        $this->connector()->get('v2/video');

        $this->assertSame(2, $attempts);
    }

    #[Test]
    #[DataProvider('writeMethodProvider')]
    public function a_write_is_not_retried_after_a_connection_failure(string $method): void
    {
        $attempts = 0;
        $this->http->fake(function () use (&$attempts): never {
            ++$attempts;

            throw new ConnectionException('Timed out');
        });

        try {
            $this->connector()->send($method, 'v2/video/1/question/batch');
            self::fail('No exception was thrown.');
        } catch (TransportException) {
            $this->assertSame(1, $attempts);
        }
    }

    #[Test]
    public function a_redirect_is_not_followed(): void
    {
        $this->http->fake([
            'api.hihaho.com/*' => Factory::response('', 302, ['Location' => 'https://elsewhere.example.com/flagged']),
            'elsewhere.example.com/*' => Factory::response(['data' => []]),
        ]);

        try {
            $this->connector()->get('v2/video');
            self::fail('No exception was thrown.');
        } catch (AuthenticationException) {
            $this->http->assertSentCount(1);
        }
    }

    #[Test]
    public function invalid_json_on_a_successful_response_throws(): void
    {
        $this->http->fake(['*' => Factory::response('<html>', 200)]);
        $connector = $this->connector();

        $this->expectException(InvalidResponseException::class);

        $connector->decode($connector->get('v2/video'));
    }

    #[Test]
    public function an_empty_success_body_decodes_to_an_empty_array(): void
    {
        $this->http->fake(['*' => Factory::response('', 204)]);
        $connector = $this->connector();

        $this->assertSame([], $connector->decode($connector->delete('v2/video/1/interaction/2')));
    }

    #[Test]
    #[DataProvider('methodProvider')]
    public function a_rate_limited_request_waits_for_retry_after_and_retries(string $method): void
    {
        $this->http->fakeSequence()
            ->push(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => '7'])
            ->push(['data' => []], 200);

        $this->connector()->send($method, 'v2/video');

        $this->http->assertSentCount(2);
        Sleep::assertSequence([Sleep::for(7)->seconds()]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function methodProvider(): iterable
    {
        yield 'get' => ['GET'];
        yield 'post' => ['POST'];
    }

    /**
     * @param  array<string, string>  $headers
     */
    #[Test]
    #[DataProvider('retryAfterProvider')]
    public function a_missing_or_malformed_retry_after_falls_back_to_doubling_backoff(array $headers): void
    {
        $this->http->fakeSequence()
            ->push([], 429, $headers)
            ->push([], 429, $headers)
            ->push([], 200);

        $this->connector()->get('v2/video');

        Sleep::assertSequence([Sleep::for(1)->seconds(), Sleep::for(2)->seconds()]);
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function retryAfterProvider(): iterable
    {
        yield 'missing' => [[]];
        yield 'http date' => [['Retry-After' => 'Wed, 21 Oct 2026 07:28:00 GMT']];
    }

    #[Test]
    public function a_retry_after_above_one_minute_throws_without_waiting(): void
    {
        $this->http->fakeSequence()
            ->push(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => '61'])
            ->push(['data' => []], 200);

        try {
            $this->connector()->get('v2/video');
            self::fail('No exception was thrown.');
        } catch (RateLimitedException) {
            $this->http->assertSentCount(1);
            Sleep::assertNeverSlept();
        }
    }

    #[Test]
    public function a_retry_after_of_one_minute_is_waited_for(): void
    {
        $this->http->fakeSequence()
            ->push(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => '60'])
            ->push(['data' => []], 200);

        $this->connector()->get('v2/video');

        Sleep::assertSequence([Sleep::for(60)->seconds()]);
    }

    #[Test]
    public function rate_limiting_throws_after_the_retries_are_used_up(): void
    {
        $this->http->fake(['*' => Factory::response(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => '1'])]);

        try {
            $this->connector()->get('v2/video');
            self::fail('No exception was thrown.');
        } catch (RateLimitedException) {
            $this->http->assertSentCount(4);
        }
    }

    #[Test]
    public function a_get_is_retried_on_a_server_error(): void
    {
        $this->http->fakeSequence()->push([], 503)->push(['data' => []], 200);

        $this->connector()->get('v2/video');

        $this->http->assertSentCount(2);
    }

    #[Test]
    #[DataProvider('writeMethodProvider')]
    public function a_write_is_not_retried_on_a_server_error(string $method): void
    {
        $this->http->fakeSequence()->push([], 500)->push([], 200);

        try {
            $this->connector()->send($method, 'v2/video/1/question/batch');
            self::fail('No exception was thrown.');
        } catch (ServerException) {
            $this->http->assertSentCount(1);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function writeMethodProvider(): iterable
    {
        yield 'post' => ['POST'];
        yield 'put' => ['PUT'];
        yield 'delete' => ['DELETE'];
    }

    #[Test]
    public function retries_can_be_switched_off(): void
    {
        $this->http->fake(['*' => Factory::response([], 429)]);

        try {
            $this->connector(RetryPolicy::disabled())->get('v2/video');
            self::fail('No exception was thrown.');
        } catch (RateLimitedException) {
            $this->http->assertSentCount(1);
        }
    }

    #[Test]
    public function switching_off_rate_limit_retries_keeps_server_error_retries(): void
    {
        $policy = new RetryPolicy(retryRateLimited: false);

        $this->http->fake(['*' => Factory::response([], 429)]);
        $this->assertThrowsAfterAttempts(RateLimitedException::class, 1, $policy);

        $this->http = new Factory();
        $this->http->fakeSequence()->push([], 503)->push(['data' => []], 200);
        $this->connector($policy)->get('v2/video');
        $this->http->assertSentCount(2);
    }

    #[Test]
    public function switching_off_server_error_retries_keeps_rate_limit_retries(): void
    {
        $policy = new RetryPolicy(retryServerErrorsOnGet: false);

        $this->http->fake(['*' => Factory::response([], 503)]);
        $this->assertThrowsAfterAttempts(ServerException::class, 1, $policy);

        $this->http = new Factory();
        $this->http->fakeSequence()->push([], 429)->push(['data' => []], 200);
        $this->connector($policy)->get('v2/video');
        $this->http->assertSentCount(2);
    }

    /**
     * @param  class-string<HttpException>  $exception
     */
    private function assertThrowsAfterAttempts(string $exception, int $attempts, RetryPolicy $policy): void
    {
        try {
            $this->connector($policy)->get('v2/video');
            self::fail('No exception was thrown.');
        } catch (HttpException $httpException) {
            $this->assertInstanceOf($exception, $httpException);
            $this->http->assertSentCount($attempts);
        }
    }

    private function connector(RetryPolicy $retryPolicy = new RetryPolicy()): Connector
    {
        return new Connector(
            http: $this->http,
            token: 'secret-token',
            retryPolicy: $retryPolicy,
        );
    }
}
