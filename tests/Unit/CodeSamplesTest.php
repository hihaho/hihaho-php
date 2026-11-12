<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Unit;

use Closure;
use ErrorException;
use Hihaho\Sdk\Hihaho;
use Hihaho\Sdk\Http\RetryPolicy;
use Hihaho\Sdk\Support\RouteMap;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * `resources/code-samples.json` holds one PHP sample per API route, for the API reference. Each
 * sample runs here against a faked API, so a sample that no longer compiles, runs or calls its
 * route fails the build.
 */
final class CodeSamplesTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/hihaho-samples-' . bin2hex(random_bytes(4));
        mkdir($this->directory);

        foreach (['video.mp4', 'poster.jpg', 'en.vtt'] as $file) {
            file_put_contents("{$this->directory}/{$file}", $file === 'en.vtt' ? 'WEBVTT' : 'bytes');
        }
    }

    protected function tearDown(): void
    {
        foreach (['video.mp4', 'poster.jpg', 'en.vtt', 'sample.php'] as $file) {
            @unlink("{$this->directory}/{$file}");
        }

        rmdir($this->directory);

        parent::tearDown();
    }

    #[Test]
    public function every_mapped_route_has_a_sample(): void
    {
        $this->assertEqualsCanonicalizing(array_keys(RouteMap::ROUTES), array_keys(self::document()['samples']));
    }

    #[Test]
    public function the_setup_creates_a_client(): void
    {
        $this->assertInstanceOf(Hihaho::class, $this->compile(self::document()['setup'], '$hihaho')(null));
    }

    #[Test]
    #[DataProvider('sampleProvider')]
    public function each_sample_runs_and_calls_its_route(string $route, string $source): void
    {
        [$method, $uri] = RouteMap::ROUTES[$route];
        $http = new Factory();
        $http->fake([
            'upload.example.com/*' => Factory::response(''),
            'api.hihaho.com/v2/video-container/7/upload-video' => fn (Request $request) => Factory::response(['data' => $request->method() === 'POST'
                ? ['id' => 9001, 'url' => 'https://upload.example.com/file', 'contentType' => 'video/mp4', 'uploadMethod' => 'axios', 'video' => ['id' => 12, 'uuid' => 'abc']]
                : []]),
            '*' => Factory::response(['data' => [], 'ids' => [1]]),
        ]);
        $hihaho = new Hihaho(token: 'token', http: $http, retryPolicy: RetryPolicy::disabled());

        ob_start();
        set_error_handler(fn (int $level, string $message): never => throw new ErrorException($message, 0, $level));

        try {
            $this->compile(str_replace('/path/to/', "{$this->directory}/", $source), 'null')($hihaho);
        } finally {
            restore_error_handler();
            ob_end_clean();
        }

        $pattern = '#^/' . preg_replace('#\\\{[^/]+\\\}#', '[^/]+', preg_quote($uri, '#')) . '$#';
        $http->assertSent(fn (Request $request): bool => $request->method() === $method
            && preg_match($pattern, (string) parse_url($request->url(), PHP_URL_PATH)) === 1);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function sampleProvider(): iterable
    {
        foreach (self::document()['samples'] as $route => $source) {
            yield $route => [$route, $source];
        }
    }

    /**
     * Wraps the sample in a function in its own file, so its `use` lines stay at file level.
     *
     * @return Closure(?Hihaho): mixed
     */
    private function compile(string $source, string $result): Closure
    {
        preg_match_all('/^use [^;]+;$/m', $source, $matches);
        $body = trim((string) preg_replace('/^use [^;]+;$/m', '', $source));
        $file = "{$this->directory}/sample.php";
        file_put_contents($file, "<?php\n" . implode("\n", $matches[0]) . "\n\nreturn static function (?\\Hihaho\\Sdk\\Hihaho \$hihaho): mixed {\n{$body}\n\nreturn {$result};\n};\n");

        /** @var Closure(?Hihaho): mixed $closure */
        $closure = require $file;

        return $closure;
    }

    /**
     * @return array{install: string, setup: string, samples: array<string, string>}
     */
    private static function document(): array
    {
        /** @var array{install: string, setup: string, samples: array<string, string>} */
        return json_decode((string) file_get_contents(__DIR__ . '/../../resources/code-samples.json'), true, flags: JSON_THROW_ON_ERROR);
    }
}
