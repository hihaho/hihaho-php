<?php declare(strict_types=1);

namespace Hihaho\Sdk;

use Hihaho\Sdk\Http\Connector;
use Hihaho\Sdk\Http\RetryPolicy;
use Hihaho\Sdk\Resources\Folders;
use Hihaho\Sdk\Resources\Uploads;
use Hihaho\Sdk\Resources\Videos;
use Hihaho\Sdk\Resources\VideoScope;
use Hihaho\Sdk\Upload\FileSender;
use Illuminate\Http\Client\Factory;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * Entry point of the SDK. Works in plain PHP and in Laravel.
 */
final readonly class Hihaho
{
    public const string DEFAULT_BASE_URL = 'https://api.hihaho.com';

    private Connector $connector;

    private Factory $http;

    public function __construct(
        #[SensitiveParameter]
        string $token,
        string $baseUrl = self::DEFAULT_BASE_URL,
        ?Factory $http = null,
        RetryPolicy $retryPolicy = new RetryPolicy(),
        int $timeoutSeconds = 30,
    ) {
        if (trim($token) === '') {
            throw new InvalidArgumentException('Set HIHAHO_API_TOKEN, or pass a token, to use the hihaho API.');
        }

        $this->http = $http ?? new Factory();
        $this->connector = new Connector(
            http: $this->http,
            token: $token,
            baseUrl: $baseUrl,
            retryPolicy: $retryPolicy,
            timeoutSeconds: $timeoutSeconds,
        );
    }

    public function folders(): Folders
    {
        return new Folders($this->connector);
    }

    public function uploads(): Uploads
    {
        return new Uploads($this->connector, new FileSender($this->http));
    }

    public function videos(): Videos
    {
        return new Videos($this->connector);
    }

    /**
     * One video: its details, interactions, questions, subtitles, thumbnails, AI tasks, sessions
     * and statistics.
     */
    public function video(int $videoId): VideoScope
    {
        return new VideoScope($this->connector, $videoId);
    }
}
