<?php declare(strict_types=1);

namespace Hihaho\Sdk\Facades;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Hihaho\Sdk\Hihaho as HihahoClient;
use Hihaho\Sdk\HihahoServiceProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Assert;

/**
 * @mixin HihahoClient
 */
final class Hihaho extends Facade
{
    /**
     * Fakes only the hihaho API; other outbound HTTP calls of the app are not faked. A pattern
     * without a host, such as `v2/video/*`, is relative to the configured base URL. A pattern that
     * starts with a scheme or a host is used as is. A batch create of interactions or questions that
     * no pattern matches answers 201 with one id per item, so `createMany()` works without setup.
     * Every other call that no pattern matches gets an empty 200 response. When no token is
     * configured, a placeholder token is set, so tests need none.
     *
     * @param  array<string, mixed>|Closure|null  $responses
     */
    public static function fake(array|Closure|null $responses = null): void
    {
        $baseUrl = rtrim(HihahoServiceProvider::baseUrl(), '/');

        if (! is_array($responses)) {
            $responses = $responses instanceof Closure ? ['*' => $responses] : [];
        }

        $scoped = [];

        foreach ($responses as $pattern => $response) {
            $scoped[self::isAbsolute($pattern) ? $pattern : $baseUrl . '/' . ltrim($pattern, '/')] = $response;
        }

        foreach (['v2/video/*/interaction/batch', 'v2/video/*/question/batch'] as $pattern) {
            $scoped[$baseUrl . '/' . $pattern] ??= self::createdIds(...);
        }

        $scoped[$baseUrl . '/*'] ??= Http::response();

        Http::fake($scoped);

        if (config()->string('hihaho.token', '') === '') {
            config()->set('hihaho.token', 'fake-token');
        }

        self::clearResolvedInstance(HihahoClient::class);
    }

    /**
     * Asserts that `video($videoId)->interactions()->createMany()` was called. The callback gets the
     * items as sent, in the API's JSON shape, and returns whether they are the expected ones.
     *
     * @param  (Closure(list<array<array-key, mixed>>): bool)|null  $callback
     */
    public static function assertCreatedInteractions(int $videoId, ?Closure $callback = null): void
    {
        self::assertBatchSent("v2/video/{$videoId}/interaction/batch", "No interactions were created for video {$videoId}.", $callback);
    }

    /**
     * @param  (Closure(list<array<array-key, mixed>>): bool)|null  $callback
     */
    public static function assertCreatedQuestions(int $videoId, ?Closure $callback = null): void
    {
        self::assertBatchSent("v2/video/{$videoId}/question/batch", "No questions were created for video {$videoId}.", $callback);
    }

    /**
     * @param  (Closure(list<array<array-key, mixed>>): bool)|null  $callback
     */
    public static function assertCreatedSubtitles(int $videoId, ?Closure $callback = null): void
    {
        self::assertBatchSent("v2/video/{$videoId}/subtitle/batch", "No subtitles were created for video {$videoId}.", $callback);
    }

    /**
     * Asserts that `video($videoId)->update()` was called. The callback gets the changed fields in the
     * API's JSON shape, for example `['display_name' => 'Safety basics']`.
     *
     * @param  (Closure(array<array-key, mixed>): bool)|null  $callback
     */
    public static function assertUpdatedVideo(int $videoId, ?Closure $callback = null): void
    {
        $sent = self::sent('PUT', "v2/video/{$videoId}", fn (array $fields): bool => ! $callback instanceof Closure || $callback($fields));

        Assert::assertTrue($sent, "Video {$videoId} was not updated as expected.");
    }

    /**
     * @param  (Closure(list<array<array-key, mixed>>): bool)|null  $callback
     */
    private static function assertBatchSent(string $path, string $message, ?Closure $callback): void
    {
        Assert::assertTrue(self::sent('POST', $path, fn (array $items): bool => ! $callback instanceof Closure || $callback(array_values(array_filter($items, is_array(...))))), $message);
    }

    /**
     * @param  Closure(array<array-key, mixed>): bool  $matches
     */
    private static function sent(string $method, string $path, Closure $matches): bool
    {
        $url = rtrim(HihahoServiceProvider::baseUrl(), '/') . '/' . $path;

        return Http::recorded(fn (Request $request): bool => $request->method() === $method
            && $request->url() === $url
            && $matches($request->data()))->isNotEmpty();
    }

    private static function createdIds(Request $request): PromiseInterface
    {
        if ($request->method() !== 'POST') {
            return Http::response(status: 204);
        }

        return Http::response(['ids' => range(1, max(1, count($request->data())))], 201);
    }

    protected static function getFacadeAccessor(): string
    {
        return HihahoClient::class;
    }

    private static function isAbsolute(string $pattern): bool
    {
        return str_contains($pattern, '://') || str_contains(explode('/', $pattern, 2)[0], '.');
    }
}
