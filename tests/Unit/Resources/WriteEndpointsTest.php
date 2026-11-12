<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Unit\Resources;

use Closure;
use Hihaho\Sdk\Enums\AiTaskType;
use Hihaho\Sdk\Enums\ChapterDensity;
use Hihaho\Sdk\Enums\CornerPosition;
use Hihaho\Sdk\Enums\Embedding;
use Hihaho\Sdk\Enums\Locale;
use Hihaho\Sdk\Enums\SettableVideoStatus;
use Hihaho\Sdk\Enums\SubtitleType;
use Hihaho\Sdk\Enums\UploadMethod;
use Hihaho\Sdk\Enums\VideoPlatform;
use Hihaho\Sdk\Enums\VideoStatus;
use Hihaho\Sdk\Exceptions\AiTaskRefusedException;
use Hihaho\Sdk\Exceptions\InvalidResponseException;
use Hihaho\Sdk\Exceptions\ValidationException;
use Hihaho\Sdk\Hihaho;
use Hihaho\Sdk\Input\AiTaskOptions;
use Hihaho\Sdk\Input\Interactions\ZoomInteraction;
use Hihaho\Sdk\Input\Questions\EssayQuestion;
use Hihaho\Sdk\Input\SubtitleInput;
use Hihaho\Sdk\Input\VideoUpdate;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class WriteEndpointsTest extends ResourceTestCase
{
    protected function tearDown(): void
    {
        Sleep::fake(false);

        parent::tearDown();
    }

    #[Test]
    public function an_update_sends_only_the_fields_that_were_set(): void
    {
        $this->respond(['data' => ['id' => 12, 'status' => 3]]);

        $this->hihaho->video(12)->update(VideoUpdate::make()
            ->displayName('Safety')
            ->authorNotes(null)
            ->status(SettableVideoStatus::Published)
            ->embedding(Embedding::AllWebsites)
            ->locale(Locale::NlNL)
            ->syncTags([]));

        $this->assertSentJson('PUT', 'v2/video/12', [
            'display_name' => 'Safety',
            'author_notes' => null,
            'status' => 3,
            'enable_embedding' => 'all_websites',
            'locale' => 'nl_NL',
            'tags' => [],
        ]);
    }

    #[Test]
    public function each_settable_status_is_a_read_status_with_the_same_value(): void
    {
        foreach (SettableVideoStatus::cases() as $status) {
            $this->assertSame($status->name, VideoStatus::from($status->value)->name);
        }

        $this->assertSame([0, 1, 2, 3, 4], array_column(SettableVideoStatus::cases(), 'value'));
    }

    #[Test]
    public function more_than_ten_tags_are_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        VideoUpdate::make()->syncTags(array_map(strval(...), range(1, 11)));
    }

    #[Test]
    public function it_creates_a_child_folder(): void
    {
        $this->respond(['data' => self::folderRow(8)], 201);

        $folder = $this->hihaho->folders()->createChild(7, 'Team', 'team-2', Locale::NlNL, userIds: [3]);

        $this->assertSame(8, $folder->id);
        $this->assertSentJson('POST', 'v2/video-container/7/create', ['name' => 'Team', 'external_identifier' => 'team-2', 'default_locale' => 'nl_NL', 'user_id' => [3]]);
    }

    #[Test]
    public function it_creates_a_video_from_a_platform(): void
    {
        $this->respond(['data' => ['id' => 13]], 201);

        $this->hihaho->videos()->createFromPlatform(7, 'https://youtu.be/abc', VideoPlatform::Youtube);

        $this->assertSentJson('POST', 'v2/video-container/7/video-from-platform', ['platform_reference' => 'https://youtu.be/abc', 'platform' => 'youtube']);
    }

    #[Test]
    public function it_registers_an_upload(): void
    {
        $this->respond(['data' => ['id' => 9001, 'url' => 'https://upload', 'contentType' => ' ', 'uploadMethod' => 'axios', 'video' => ['id' => 12, 'uuid' => 'abc']]]);

        $ticket = $this->hihaho->uploads()->register(7, 'Safety.mp4', 52428800, 183000);

        $this->assertSame(UploadMethod::Axios, $ticket->uploadMethod);
        $this->assertSame(' ', $ticket->contentType);
        $this->assertSame('abc', $ticket->video?->uuid);
        $this->assertSentJson('POST', 'v2/video-container/7/upload-video', ['name' => 'Safety.mp4', 'size' => 52428800, 'duration_in_ms' => 183000]);
    }

    #[Test]
    public function a_replacement_upload_has_no_video(): void
    {
        $this->respond(['data' => ['id' => 9002, 'url' => 'https://upload', 'contentType' => '', 'uploadMethod' => 'axios', 'video' => null]]);

        $this->assertNull($this->hihaho->uploads()->register(7, replaceVideoIds: [12])->video);
    }

    #[Test]
    public function it_finishes_an_upload(): void
    {
        $this->respond('', 204);

        $this->hihaho->uploads()->finish(9001);

        $this->assertRequested('POST', 'v2/upload-video/9001/finished');
    }

    #[Test]
    #[DataProvider('batchProvider')]
    public function a_batch_sends_the_ids_in_the_body(string $method, string $path, Closure $call): void
    {
        $this->respond('', 204);

        $call($this->hihaho);

        $this->assertSentJson($method, $path, ['id' => [1, 2]]);
    }

    /**
     * @return iterable<string, array{string, string, Closure(Hihaho): void}>
     */
    public static function batchProvider(): iterable
    {
        yield 'delete videos' => ['DELETE', 'v2/video/batch', function (Hihaho $hihaho): void {
            $hihaho->videos()->deleteMany([1, 2]);
        }];
        yield 'move videos' => ['PUT', 'v2/video/batch/move/7', function (Hihaho $hihaho): void {
            $hihaho->videos()->moveMany([1, 2], 7);
        }];
        yield 'delete interactions' => ['DELETE', 'v2/video/12/interaction/batch', function (Hihaho $hihaho): void {
            $hihaho->video(12)->interactions()->deleteMany([1, 2]);
        }];
        yield 'delete questions' => ['DELETE', 'v2/video/12/question/batch', function (Hihaho $hihaho): void {
            $hihaho->video(12)->questions()->deleteMany([1, 2]);
        }];
    }

    #[Test]
    #[DataProvider('oversizedBatchProvider')]
    public function a_batch_over_the_maximum_is_refused_before_sending(Closure $call): void
    {
        $this->respond('', 204);

        try {
            $call($this->hihaho);
            self::fail('No exception was thrown.');
        } catch (InvalidArgumentException) {
            $this->http->assertNothingSent();
        }
    }

    /**
     * @return iterable<string, array{Closure(Hihaho): void}>
     */
    public static function oversizedBatchProvider(): iterable
    {
        yield '201 videos to delete' => [function (Hihaho $hihaho): void {
            $hihaho->videos()->deleteMany(range(1, 201));
        }];
        yield '101 videos to move' => [function (Hihaho $hihaho): void {
            $hihaho->videos()->moveMany(range(1, 101), 7);
        }];
        yield '101 interactions' => [function (Hihaho $hihaho): void {
            $hihaho->video(12)->interactions()->deleteMany(range(1, 101));
        }];
        yield '101 questions' => [function (Hihaho $hihaho): void {
            $hihaho->video(12)->questions()->deleteMany(range(1, 101));
        }];
        yield 'no ids' => [function (Hihaho $hihaho): void {
            $hihaho->videos()->deleteMany([]);
        }];
    }

    #[Test]
    #[DataProvider('maximumBatchProvider')]
    public function a_batch_at_the_maximum_is_sent(Closure $call, int $count): void
    {
        $this->respond('', 204);

        $call($this->hihaho, range(1, $count));

        $this->http->assertSentCount(1);
    }

    /**
     * @return iterable<string, array{Closure(Hihaho, list<int>): void, int}>
     */
    public static function maximumBatchProvider(): iterable
    {
        yield '200 videos to delete' => [fn (Hihaho $hihaho, array $ids) => $hihaho->videos()->deleteMany($ids), 200];
        yield '100 videos to move' => [fn (Hihaho $hihaho, array $ids) => $hihaho->videos()->moveMany($ids, 7), 100];
        yield '100 interactions' => [fn (Hihaho $hihaho, array $ids) => $hihaho->video(12)->interactions()->deleteMany($ids), 100];
        yield '100 questions' => [fn (Hihaho $hihaho, array $ids) => $hihaho->video(12)->questions()->deleteMany($ids), 100];
    }

    #[Test]
    public function swap_uses_uuids_and_reads_both_videos(): void
    {
        $this->respond(['videos' => ['updated_from' => ['id' => 1, 'uuid' => 'b'], 'updated_to' => ['id' => 2, 'uuid' => 'a']]]);

        $swapped = $this->hihaho->videos()->swap('a', 'b');

        $this->assertRequested('POST', 'v2/video/swap/from/a/to/b');
        $this->assertSame('b', $swapped->from->uuid);
        $this->assertSame(2, $swapped->to->id);
        $this->assertSame(['videos' => ['updated_from' => ['id' => 1, 'uuid' => 'b'], 'updated_to' => ['id' => 2, 'uuid' => 'a']]], $swapped->raw());
    }

    #[Test]
    public function it_creates_a_viewer_token(): void
    {
        $this->respond(['token' => 'tok', 'expires_at' => null, 'player_url' => 'https://p', 'embed_url' => 'https://e']);

        $token = $this->hihaho->video(12)->createViewerToken('viewer-1', ['team-a'], 3600);

        $this->assertSame('tok', $token->token);
        $this->assertNull($token->expiresAt);
        $this->assertSentJson('POST', 'v2/video/12/allowed_viewer_token', ['viewer_uid' => 'viewer-1', 'variables' => ['team-a'], 'lifetime' => 3600]);
    }

    #[Test]
    public function it_starts_an_ai_task(): void
    {
        $this->respond(['message' => 'Queued.', 'tasks' => [['id' => 3, 'task_type' => 'generate_chapters', 'status' => 'pending']]], 201);

        $started = $this->hihaho->video(12)->aiTasks()->start(AiTaskType::GenerateChapters, options: new AiTaskOptions(chapterDensity: ChapterDensity::More));

        $this->assertSame('Queued.', $started->message);
        $this->assertSentJson('POST', 'v2/video/12/ai-task', ['task_type' => 'generate_chapters', 'user_input' => ['chapter_density' => 'more']]);
    }

    #[Test]
    public function a_translation_task_needs_a_target_locale(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->hihaho->video(12)->aiTasks()->start(AiTaskType::TranslateSubtitles);
    }

    #[Test]
    public function a_task_type_the_api_cannot_start_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->hihaho->video(12)->aiTasks()->start(AiTaskType::DubAudio);
    }

    #[Test]
    public function a_refused_ai_task_carries_the_latest_tasks(): void
    {
        $this->respond(['message' => 'An AI action is already in progress', 'tasks' => [['id' => 3]]], 400);

        try {
            $this->hihaho->video(12)->aiTasks()->start(AiTaskType::Transcribe);
            self::fail('No exception was thrown.');
        } catch (AiTaskRefusedException $aiTaskRefusedException) {
            $this->assertSame([['id' => 3]], $aiTaskRefusedException->tasks());
        }
    }

    #[Test]
    public function subtitles_are_sent_as_a_top_level_list(): void
    {
        $this->respond('', 204);

        $this->hihaho->video(12)->subtitles()->createMany([new SubtitleInput('English', 'en', "1\n00:00:01,000 --> 00:00:02,000\nHi", SubtitleType::Srt, isDefault: true, buttonPosition: CornerPosition::TopLeft), new SubtitleInput('Dutch', 'nl', 'WEBVTT', buttonPosition: CornerPosition::TopLeft)]);

        $this->assertSentJson('POST', 'v2/video/12/subtitle/batch', [
            ['title' => 'English', 'language' => 'en', 'content' => "1\n00:00:01,000 --> 00:00:02,000\nHi", 'type' => 'srt', 'is_default' => true, 'button_position' => 'top-left'],
            ['title' => 'Dutch', 'language' => 'nl', 'content' => 'WEBVTT', 'is_default' => false],
        ]);
    }

    #[Test]
    public function a_subtitle_track_is_read_from_a_file(): void
    {
        $path = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8)) . '.VTT';
        file_put_contents($path, 'WEBVTT');

        try {
            $input = SubtitleInput::fromFile($path, title: 'English', language: 'en', isDefault: true);
        } finally {
            unlink($path);
        }

        $this->assertSame(['title' => 'English', 'language' => 'en', 'content' => 'WEBVTT', 'type' => 'vtt', 'is_default' => true], $input->toArray());
    }

    #[Test]
    public function a_subtitle_file_with_an_unknown_extension_needs_a_type(): void
    {
        $path = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8)) . '.txt';
        file_put_contents($path, '1');

        try {
            $this->assertSame(SubtitleType::Srt, SubtitleInput::fromFile($path, 'English', 'en', SubtitleType::Srt)->type);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Cannot tell the subtitle type');
            SubtitleInput::fromFile($path, 'English', 'en');
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function a_missing_subtitle_file_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot read /no/such/track.vtt.');

        SubtitleInput::fromFile('/no/such/track.vtt', 'English', 'en');
    }

    #[Test]
    public function a_batch_import_422_names_the_item(): void
    {
        $this->respond(['message' => 'Invalid.', 'errors' => ['1.title' => ['The title field is required.']]], 422);

        try {
            $this->hihaho->video(12)->subtitles()->createMany([new SubtitleInput('English', 'en', 'x'), new SubtitleInput('', 'nl', 'y')]);
            self::fail('No exception was thrown.');
        } catch (ValidationException $validationException) {
            $this->assertSame(['1.title' => ['The title field is required.']], $validationException->errors());
            $this->assertSame('Invalid.', $validationException->getMessage());
        }
    }

    #[Test]
    public function interactions_and_questions_are_stored_as_top_level_lists(): void
    {
        $this->respond(['ids' => [31, 32]], 201);

        $this->assertSame([31, 32], $this->hihaho->video(12)->interactions()->createMany([new ZoomInteraction(startTime: 1, endTime: 2), new ZoomInteraction(startTime: 3, endTime: 4)]));
        $this->assertSame([31, 32], $this->hihaho->video(12)->questions()->createMany([new EssayQuestion(5, 'Why?'), new EssayQuestion(6, 'How?')]));

        $this->assertSentJson('POST', 'v2/video/12/interaction/batch', [['type' => 'zoom', 'start_time' => 1.0, 'end_time' => 2.0], ['type' => 'zoom', 'start_time' => 3.0, 'end_time' => 4.0]]);
        $this->assertSentJson('POST', 'v2/video/12/question/batch', [['type' => 'essay', 'active_at' => 5.0, 'text' => 'Why?'], ['type' => 'essay', 'active_at' => 6.0, 'text' => 'How?']]);
    }

    #[Test]
    public function a_batch_store_without_an_ids_list_is_refused(): void
    {
        $this->respond('', 204);

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('without an `ids` list');

        $this->hihaho->video(12)->interactions()->createMany([new ZoomInteraction(startTime: 1, endTime: 2)]);
    }

    #[Test]
    public function thumbnail_upload_is_multipart(): void
    {
        $this->respond(['data' => ['id' => 12]]);
        $path = tempnam(sys_get_temp_dir(), 'thumb') . '.jpg';
        file_put_contents($path, 'jpeg-bytes');

        $this->hihaho->video(12)->thumbnails()->upload($path);

        $this->http->assertSent(fn (Request $request): bool => $request->url() === 'https://api.hihaho.com/v2/video/12/thumbnail'
            && $request->isMultipart()
            && $this->firstPartName($request) === 'thumbnail');
        unlink($path);
    }

    #[Test]
    public function a_retried_thumbnail_upload_sends_the_whole_file_again(): void
    {
        Sleep::fake();
        $hihaho = new Hihaho(token: 'token', http: $this->http);
        $bodies = [];
        $this->http->fake(function (Request $request) use (&$bodies) {
            // getContents() reads from the current position; body() rewinds first and would hide a missing rewind.
            $bodies[] = $request->toPsrRequest()->getBody()->getContents();

            return count($bodies) === 1 ? Factory::response([], 429, ['Retry-After' => '1']) : Factory::response(['data' => ['id' => 12]]);
        });
        $path = tempnam(sys_get_temp_dir(), 'thumb') . '.jpg';
        file_put_contents($path, 'jpeg-bytes');

        $hihaho->video(12)->thumbnails()->upload($path);

        $this->assertCount(2, $bodies);
        $this->assertStringContainsString('jpeg-bytes', $bodies[1]);
        unlink($path);
    }

    #[Test]
    public function thumbnail_ids_are_url_encoded(): void
    {
        $this->respond('', 204);

        $this->hihaho->video(12)->thumbnails()->activate('abc/def');

        $this->assertRequested('PUT', 'v2/video/12/thumbnail/abc%2Fdef/activate');
    }

    private function firstPartName(Request $request): mixed
    {
        $part = $request->data()[0] ?? null;

        return is_array($part) ? ($part['name'] ?? null) : null;
    }

    /**
     * @param  array<array-key, mixed>  $json
     */
    private function assertSentJson(string $method, string $path, array $json): void
    {
        $this->http->assertSent(fn (Request $request): bool => $request->method() === $method
            && $request->url() === 'https://api.hihaho.com/' . $path
            && $request->data() === $json);
    }
}
