<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Unit\Resources;

use Closure;
use Hihaho\Sdk\Enums\AiTaskType;
use Hihaho\Sdk\Enums\VideoPlatform;
use Hihaho\Sdk\Hihaho;
use Hihaho\Sdk\Input\Interactions\ZoomInteraction;
use Hihaho\Sdk\Input\Questions\EssayQuestion;
use Hihaho\Sdk\Input\SubtitleInput;
use Hihaho\Sdk\Input\VideoUpdate;
use Hihaho\Sdk\Support\RouteMap;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class RouteCallTest extends ResourceTestCase
{
    #[Test]
    public function every_mapped_route_has_a_call(): void
    {
        $this->assertEqualsCanonicalizing(array_keys(RouteMap::ROUTES), array_keys(self::calls()));
    }

    /**
     * @param  Closure(Hihaho): mixed  $call
     */
    #[Test]
    #[DataProvider('routeProvider')]
    public function each_method_calls_its_mapped_route(string $method, string $uri, Closure $call): void
    {
        $this->http->fake(['*' => Factory::response(['data' => [], 'ids' => []])]);

        $call($this->hihaho);

        $this->http->assertSentCount(1);
        $pattern = '#^/' . preg_replace('#\\\{[^/]+\\\}#', '[^/]+', preg_quote($uri, '#')) . '$#';
        $this->http->assertSent(fn (Request $request): bool => $request->method() === $method
            && preg_match($pattern, (string) parse_url($request->url(), PHP_URL_PATH)) === 1);
    }

    /**
     * @return iterable<string, array{string, string, Closure(Hihaho): mixed}>
     */
    public static function routeProvider(): iterable
    {
        $calls = self::calls();

        foreach (RouteMap::ROUTES as $route => [$method, $uri]) {
            if (isset($calls[$route])) {
                yield $route => [$method, $uri, $calls[$route]];
            }
        }
    }

    /**
     * @return array<string, Closure(Hihaho): mixed>
     */
    private static function calls(): array
    {
        return [
            'api.v2.video-container.index' => fn (Hihaho $h): mixed => $h->folders()->list()->page(),
            'api.v2.video-container.create-child' => fn (Hihaho $h): mixed => $h->folders()->createChild(7, 'Team', 'team-2'),
            'api.v2.video-container.upload-video.index' => fn (Hihaho $h): mixed => $h->uploads()->list(7)->page(),
            'api.v2.video-container.upload-video.store' => fn (Hihaho $h): mixed => $h->uploads()->register(7, 'a.mp4', 1000),
            'api.v2.video-container.upload-video.destroy.failed' => function (Hihaho $h): void {
                $h->uploads()->deleteFailed(7);
            },
            'api.v2.video-container.video-from-platform' => fn (Hihaho $h): mixed => $h->videos()->createFromPlatform(7, 'abc', VideoPlatform::cases()[0]),
            'api.v2.upload-video.video.show' => fn (Hihaho $h): mixed => $h->uploads()->get(9001),
            'api.v2.upload-video.video.store' => fn (Hihaho $h): mixed => $h->uploads()->createVideo(9001),
            'api.v2.upload-video.finished' => function (Hihaho $h): void {
                $h->uploads()->finish(9001);
            },
            'api.v2.video.index' => fn (Hihaho $h): mixed => $h->videos()->list()->page(),
            'api.v2.video.show' => fn (Hihaho $h): mixed => $h->video(12)->get(),
            'api.v2.video.update' => fn (Hihaho $h): mixed => $h->video(12)->update(VideoUpdate::make()->displayName('Safety')),
            'api.v2.video.export' => fn (Hihaho $h): mixed => $h->video(12)->export(),
            'api.v2.video.destroy.batch' => function (Hihaho $h): void {
                $h->videos()->deleteMany([12]);
            },
            'api.v2.video.move.batch' => function (Hihaho $h): void {
                $h->videos()->moveMany([12], 7);
            },
            'api.v2.video.swap-uuid' => fn (Hihaho $h): mixed => $h->videos()->swap('from-uuid', 'to-uuid'),
            'api.v2.video.allowed-viewer-token.store' => fn (Hihaho $h): mixed => $h->video(12)->createViewerToken('viewer-1'),
            'api.v2.video.thumbnail.update' => self::uploadThumbnail(...),
            'api.v2.video.thumbnail.index' => fn (Hihaho $h): mixed => $h->video(12)->thumbnails()->list()->page(),
            'api.v2.video.thumbnail.activate' => function (Hihaho $h): void {
                $h->video(12)->thumbnails()->activate('thumb-1');
            },
            'api.v2.video.thumbnail.destroy' => function (Hihaho $h): void {
                $h->video(12)->thumbnails()->delete('thumb-1');
            },
            'api.v2.video.ai-task.index' => fn (Hihaho $h): mixed => $h->video(12)->aiTasks()->list()->page(),
            'api.v2.video.ai-task.create' => fn (Hihaho $h): mixed => $h->video(12)->aiTasks()->start(AiTaskType::Transcribe),
            'api.v2.video.subtitles' => fn (Hihaho $h): mixed => $h->video(12)->subtitles()->list()->page(),
            'api.v2.video.subtitle.store.batch' => function (Hihaho $h): void {
                $h->video(12)->subtitles()->createMany([new SubtitleInput('English', 'en', 'WEBVTT')]);
            },
            'api.v2.video.statistics.aggregated' => fn (Hihaho $h): mixed => $h->video(12)->statistics()->aggregated(),
            'api.v2.video.session.index' => fn (Hihaho $h): mixed => $h->video(12)->sessions()->list()->page(),
            'api.v2.video.session.show' => fn (Hihaho $h): mixed => $h->video(12)->sessions()->get(3),
            'api.v2.video.stats.views' => fn (Hihaho $h): mixed => $h->video(12)->statistics()->views(),
            'api.v2.video.stats.views-by-user-agent' => fn (Hihaho $h): mixed => $h->video(12)->statistics()->viewsByUserAgent(),
            'api.v2.video.stats.interactions' => fn (Hihaho $h): mixed => $h->video(12)->statistics()->interactions()->page(),
            'api.v2.video.stats.questions' => fn (Hihaho $h): mixed => $h->video(12)->statistics()->questions()->page(),
            'api.v2.video.stats.metadata.list' => fn (Hihaho $h): mixed => $h->video(12)->statistics()->metadata()->page(),
            'api.v2.video.stats.metadata.show' => fn (Hihaho $h): mixed => $h->video(12)->statistics()->sessionMetadata(3),
            'api.v2.video.interaction.index' => fn (Hihaho $h): mixed => $h->video(12)->interactions()->list()->page(),
            'api.v2.video.interaction.show' => fn (Hihaho $h): mixed => $h->video(12)->interactions()->get(4),
            'api.v2.video.interaction.store.batch' => function (Hihaho $h): void {
                $h->video(12)->interactions()->createMany([new ZoomInteraction(startTime: 1, endTime: 2)]);
            },
            'api.v2.video.interaction.destroy.batch' => function (Hihaho $h): void {
                $h->video(12)->interactions()->deleteMany([4]);
            },
            'api.v2.video.interaction.destroy' => function (Hihaho $h): void {
                $h->video(12)->interactions()->delete(4);
            },
            'api.v2.video.question.index' => fn (Hihaho $h): mixed => $h->video(12)->questions()->list()->page(),
            'api.v2.video.question.show' => fn (Hihaho $h): mixed => $h->video(12)->questions()->get(5),
            'api.v2.video.question.store.batch' => function (Hihaho $h): void {
                $h->video(12)->questions()->createMany([new EssayQuestion(5, 'Why?')]);
            },
            'api.v2.video.question.destroy.batch' => function (Hihaho $h): void {
                $h->video(12)->questions()->deleteMany([5]);
            },
            'api.v2.video.question.destroy' => function (Hihaho $h): void {
                $h->video(12)->questions()->delete(5);
            },
        ];
    }

    private static function uploadThumbnail(Hihaho $hihaho): mixed
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'thumbnail');

        try {
            return $hihaho->video(12)->thumbnails()->upload($path);
        } finally {
            @unlink($path);
        }
    }
}
