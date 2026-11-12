<?php declare(strict_types=1);

namespace Hihaho\Sdk\Http;

use Composer\InstalledVersions;
use Hihaho\Sdk\Exceptions\InvalidResponseException;
use Hihaho\Sdk\Exceptions\TransportException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * Sends requests to the hihaho API host and maps failures to SDK exceptions.
 */
final readonly class Connector
{
    private const int MAX_RETRY_AFTER_SECONDS = 60;

    private string $userAgent;

    public function __construct(
        private Factory $http,
        #[SensitiveParameter]
        private string $token,
        private string $baseUrl = 'https://api.hihaho.com',
        private RetryPolicy $retryPolicy = new RetryPolicy(),
        private int $timeoutSeconds = 30,
        private ErrorMapper $errorMapper = new ErrorMapper(),
    ) {
        $scheme = strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));

        if ($scheme !== 'https' && ($scheme !== 'http' || ! in_array($host, ['localhost', '127.0.0.1'], true))) {
            throw new InvalidArgumentException('The base URL must use https; only localhost and 127.0.0.1 may use http.');
        }

        $this->userAgent = "hihaho-php/{$this->version()} PHP/" . PHP_VERSION;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'token' => '[redacted]',
            'baseUrl' => $this->baseUrl,
            'retryPolicy' => $this->retryPolicy,
            'timeoutSeconds' => $this->timeoutSeconds,
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = []): Response
    {
        return $this->send('GET', $path, ['query' => $query]);
    }

    /**
     * @param  array<array-key, mixed>  $body
     */
    public function post(string $path, array $body = []): Response
    {
        return $this->send('POST', $path, ['json' => $body]);
    }

    /**
     * @param  array<array-key, mixed>  $body
     */
    public function put(string $path, array $body = []): Response
    {
        return $this->send('PUT', $path, ['json' => $body]);
    }

    /**
     * @param  array<array-key, mixed>  $body
     */
    public function delete(string $path, array $body = []): Response
    {
        return $this->send('DELETE', $path, $body === [] ? [] : ['json' => $body]);
    }

    public function postFile(string $path, string $field, string $filePath): Response
    {
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException("Cannot read {$filePath}.");
        }

        try {
            // A 429 retry sends the file again, so every attempt starts at the beginning of the stream.
            return $this->send('POST', $path, ['multipart' => []], function (PendingRequest $request) use ($field, $handle, $filePath): PendingRequest {
                rewind($handle);

                return $request->attach($field, $handle, basename($filePath));
            });
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  (callable(PendingRequest): PendingRequest)|null  $prepare
     */
    public function send(string $method, string $path, array $options = [], ?callable $prepare = null): Response
    {
        $attempt = 0;

        while (true) {
            $request = $this->request();

            if ($prepare !== null) {
                $request = $prepare($request);
            }

            try {
                $response = $request->send($method, $this->url($path), $options);
                // The stats hold the request with its bearer token; an exception trace that holds this response must not.
                $response->transferStats = null;
            } catch (ConnectionException $exception) {
                if ($this->shouldRetryConnectionFailure($method, $attempt)) {
                    $this->wait($this->backoff($attempt));
                    ++$attempt;

                    continue;
                }

                throw TransportException::from($exception->getMessage());
            }

            if ($this->shouldRetry($method, $response, $attempt)) {
                $this->wait($this->retryDelay($response, $attempt));
                ++$attempt;

                continue;
            }

            if ($response->successful()) {
                return $response;
            }

            $body = $response->json();

            throw $this->errorMapper->map($response->status(), is_array($body) ? $body : [], ErrorMapper::headersOf($response));
        }
    }

    /**
     * Decodes a successful response body. An empty body (204) gives an empty array.
     *
     * @return array<array-key, mixed>
     */
    public function decode(Response $response): array
    {
        if (trim($response->body()) === '') {
            return [];
        }

        $data = json_decode($response->body(), true);

        if (! is_array($data)) {
            throw InvalidResponseException::notJson($response);
        }

        return $data;
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->createPendingRequest()
            ->withToken($this->token)
            ->acceptJson()
            ->withUserAgent($this->userAgent)
            ->withOptions(['allow_redirects' => false])
            ->timeout($this->timeoutSeconds);
    }

    private function version(): string
    {
        return InstalledVersions::isInstalled('hihaho/hihaho-php')
            ? InstalledVersions::getPrettyVersion('hihaho/hihaho-php') ?? 'dev'
            : 'dev';
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }

    private function shouldRetry(string $method, Response $response, int $attempt): bool
    {
        if ($attempt >= $this->retryPolicy->maxRetries) {
            return false;
        }

        if ($response->status() === 429) {
            return $this->retryPolicy->retryRateLimited && $this->retryAfter($response) <= self::MAX_RETRY_AFTER_SECONDS;
        }

        return $response->serverError() && $this->isRetryableServerError($method);
    }

    private function shouldRetryConnectionFailure(string $method, int $attempt): bool
    {
        return $attempt < $this->retryPolicy->maxRetries && $this->isRetryableServerError($method);
    }

    private function isRetryableServerError(string $method): bool
    {
        return $this->retryPolicy->retryServerErrorsOnGet && $method === 'GET';
    }

    private function retryDelay(Response $response, int $attempt): int
    {
        return $response->status() === 429
            ? $this->retryAfter($response) ?? $this->backoff($attempt)
            : $this->backoff($attempt);
    }

    private function retryAfter(Response $response): ?int
    {
        $retryAfter = $response->header('Retry-After');

        return ctype_digit($retryAfter) ? (int) $retryAfter : null;
    }

    private function backoff(int $attempt): int
    {
        return $this->retryPolicy->initialBackoffSeconds * (2 ** $attempt);
    }

    private function wait(int $seconds): void
    {
        Sleep::for($seconds)->seconds();
    }
}
