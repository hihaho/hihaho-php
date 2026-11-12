<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Unit\Resources;

use Carbon\CarbonImmutable;
use Closure;
use Hihaho\Sdk\Data\AiTask;
use Hihaho\Sdk\Data\ViewStatistics;
use Hihaho\Sdk\Enums\AiTaskStatus;
use Hihaho\Sdk\Enums\AiTaskType;
use Hihaho\Sdk\Enums\Embedding;
use Hihaho\Sdk\Enums\GroupByPeriod;
use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Enums\Locale;
use Hihaho\Sdk\Enums\PerPage;
use Hihaho\Sdk\Enums\QuestionType;
use Hihaho\Sdk\Enums\RubricMatchStatus;
use Hihaho\Sdk\Enums\UploadVideoStatus;
use Hihaho\Sdk\Enums\VideoStatus;
use Hihaho\Sdk\Exceptions\InvalidResponseException;
use Hihaho\Sdk\Exceptions\PreconditionFailedException;
use Hihaho\Sdk\Hihaho;
use Hihaho\Sdk\Input\StatisticsFilter;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class ReadEndpointsTest extends ResourceTestCase
{
    #[Test]
    public function it_lists_folders(): void
    {
        $this->respond(self::paginated([self::folderRow()]));

        $folders = $this->hihaho->folders()->list()->all()->all();

        $this->assertRequested('GET', 'v2/video-container?page=1');
        $this->assertSame('Training', $folders[0]->name);
        $this->assertSame('2026-10-15 14:09:51', $folders[0]->createdAt?->format('Y-m-d H:i:s'));
        $this->assertNull($folders[0]->parentId);
    }

    /**
     * @param  array<array-key, mixed>  $body
     * @param  Closure(Hihaho): mixed  $call
     */
    #[Test]
    #[DataProvider('missingDataProvider')]
    public function a_body_without_its_data_envelope_throws(array $body, Closure $call): void
    {
        $this->respond($body);

        $this->expectException(InvalidResponseException::class);

        $call($this->hihaho);
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>, Closure(Hihaho): mixed}>
     */
    public static function missingDataProvider(): iterable
    {
        $get = fn (Hihaho $hihaho): mixed => $hihaho->video(12)->get();
        $write = fn (Hihaho $hihaho): mixed => $hihaho->folders()->createChild(7, 'Team', 'team-2');
        $paged = fn (Hihaho $hihaho): mixed => $hihaho->folders()->list()->page();
        $bare = fn (Hihaho $hihaho): mixed => $hihaho->video(12)->interactions()->list()->page();

        yield 'get without data' => [['id' => 12], $get];
        yield 'get with a string as data' => [['data' => 'video'], $get];
        yield 'write without data' => [['id' => 8], $write];
        yield 'page without data' => [['meta' => ['last_page' => 1]], $paged];
        yield 'bare list object without data' => [['message' => 'ok'], $bare];
    }

    #[Test]
    public function a_video_scope_sends_nothing_until_a_method_is_called(): void
    {
        $this->respond(['data' => []]);

        $interactions = $this->hihaho->video(12)->interactions();

        $this->http->assertNothingSent();
        $this->assertSame([], $interactions->list()->page()->items);
    }

    #[Test]
    public function it_lists_videos_with_filters(): void
    {
        $this->respond(self::paginated([[
            'created_at' => '2026-10-15T14:09:51.000000Z', 'updated_at' => '2026-10-15T14:09:51.000000Z',
            'description' => null, 'display_name' => 'Safety', 'id' => 12, 'status' => 3, 'uuid' => 'abc',
            'video_container_id' => 7, 'video_container' => self::folderRow(),
        ]]));

        $videos = $this->hihaho->videos()->list(search: 'safety', status: VideoStatus::Published, folderId: 7)->page();

        $this->assertRequested('GET', 'v2/video?q=safety&status=3&video_container_id=7&page=1');
        $this->assertSame(VideoStatus::Published, $videos->items[0]->status);
        $this->assertSame(7, $videos->items[0]->videoContainer->id);
    }

    #[Test]
    public function it_gets_a_video(): void
    {
        $this->respond(['data' => [
            'author_notes' => null, 'created_at' => '2026-10-15 14:09:51', 'description' => null, 'display_name' => 'Safety',
            'embed_url' => 'https://e', 'id' => 12, 'iframe_element' => '<iframe>', 'player_url' => 'https://p', 'status' => 2,
            'thumbnails' => ['big' => 'b.jpg', 'small' => 's.jpg'], 'uuid' => 'abc', 'video_container_id' => 7,
            'video_container' => self::folderRow(), 'start_time' => 0, 'locale' => 'nl_NL', 'end_time' => 60000,
            'duration' => 60000, 'tags' => ['a', 'b'], 'updated_at' => '2026-10-15 14:09:51', 'should_allow_seeking' => null,
            'effective_should_allow_seeking' => true, 'enable_embedding' => 'my_websites', 'allowed_domains' => ['hihaho.com'],
            'container_allowed_domains' => null, 'effective_allowed_domains' => ['hihaho.com'],
        ]]);

        $video = $this->hihaho->video(12)->get();

        $this->assertRequested('GET', 'v2/video/12');
        $this->assertSame(Locale::NlNL, $video->locale);
        $this->assertSame(Embedding::MyWebsites, $video->enableEmbedding);
        $this->assertSame(['a', 'b'], $video->tags);
        $this->assertSame('b.jpg', $video->thumbnails->big);
        $this->assertNull($video->containerAllowedDomains);
        $this->assertTrue($video->effectiveShouldAllowSeeking);
    }

    #[Test]
    public function an_unknown_enum_value_keeps_the_raw_value(): void
    {
        $this->respond(['data' => ['id' => 1, 'status' => 99, 'created_at' => null, 'video_name' => null, 'platform_reference' => null,
            'status_description' => 'New', 'uploaded_by' => ['id' => 2, 'name' => 'Ann'], 'video_container' => self::folderRow(), 'videos' => []]]);

        $upload = $this->hihaho->uploads()->get(1);

        $this->assertNull($upload->status);
        $this->assertSame(99, $upload->statusValue);
    }

    #[Test]
    public function an_unknown_field_is_reachable_through_raw(): void
    {
        $this->respond(['data' => [...self::folderRow(), 'brand_new_field' => 'x']]);

        $upload = $this->hihaho->uploads()->get(1);

        $this->assertSame('x', $upload->raw()['brand_new_field']);
    }

    #[Test]
    public function it_gets_an_upload_with_its_videos(): void
    {
        $this->respond(['data' => ['id' => 9001, 'status' => 6, 'status_description' => 'Finished', 'created_at' => '2026-10-15T14:09:51.370347Z',
            'video_name' => 'Safety.mp4', 'platform_reference' => 'key', 'uploaded_by' => ['id' => 2, 'name' => 'Ann'],
            'video_container' => self::folderRow(), 'videos' => [['id' => 12, 'uuid' => 'abc', 'display_name' => 'Safety', 'status' => 5, 'video_container_id' => 7, 'video_container' => self::folderRow()]]]]);

        $upload = $this->hihaho->uploads()->get(9001);

        $this->assertSame(UploadVideoStatus::Ready, $upload->status);
        $this->assertTrue($upload->status->isComplete());
        $this->assertSame('Ann', $upload->uploadedBy->name);
        $this->assertSame(VideoStatus::Processing, $upload->videos[0]->status);
    }

    #[Test]
    public function it_walks_the_paginated_ai_task_list(): void
    {
        $this->http->fakeSequence()
            ->push(self::paginated([$this->aiTaskRow(3)], page: 1, last: 2))
            ->push(self::paginated([$this->aiTaskRow(2)], page: 2, last: 2));

        $tasks = $this->hihaho->video(12)->aiTasks()->list()->all()->all();

        $this->assertRequested('GET', 'v2/video/12/ai-task?page=1');
        $this->assertRequested('GET', 'v2/video/12/ai-task?page=2');
        $this->assertSame([3, 2], array_map(fn (AiTask $task): int => $task->id, $tasks));
        $this->assertSame(AiTaskType::GenerateChapters, $tasks[0]->taskType);
        $this->assertSame(AiTaskStatus::Running, $tasks[0]->status);
        $this->assertNull($tasks[0]->completedAt);
    }

    #[Test]
    public function an_ai_task_list_without_pagination_reads_one_page(): void
    {
        $this->respond(['data' => [$this->aiTaskRow(3)]]);

        $tasks = $this->hihaho->video(12)->aiTasks()->list()->all()->all();

        $this->http->assertSentCount(1);
        $this->assertSame([3], array_map(fn (AiTask $task): int => $task->id, $tasks));
    }

    #[Test]
    public function a_single_question_is_read_unwrapped(): void
    {
        $this->respond(['id' => 4, 'video_id' => 12, 'type' => 'mc', 'text' => 'Pick one', 'time' => 12, 'points' => 1,
            'difficulty' => 'easy', 'answers' => [['id' => 1, 'text' => 'A', 'correct' => true]], 'evaluation_rubrics' => []]);

        $question = $this->hihaho->video(12)->questions()->get(4);

        $this->assertRequested('GET', 'v2/video/12/question/4');
        $this->assertSame(QuestionType::MultipleChoice, $question->type);
        $this->assertEqualsWithDelta(12.0, $question->time, PHP_FLOAT_EPSILON);
        $this->assertCount(1, $question->answers);
    }

    #[Test]
    public function an_interaction_is_read_wrapped(): void
    {
        $this->respond(['data' => ['id' => 5, 'video_id' => 12, 'type' => 'chapter', 'title' => 'Intro', 'text' => '', 'time' => 0,
            'duration' => 10.5, 'end_time' => 10.5, 'locale' => null, 'action' => ['type' => 0], 'chapters' => []]]);

        $interaction = $this->hihaho->video(12)->interactions()->get(5);

        $this->assertSame(InteractionType::Chapter, $interaction->type);
        $this->assertEqualsWithDelta(10.5, $interaction->duration, PHP_FLOAT_EPSILON);
        $this->assertSame(['type' => 0], $interaction->action);
    }

    #[Test]
    public function session_list_sends_the_filter_and_page_size(): void
    {
        $this->respond(self::paginated([['id' => 1, 'created_at' => '2026-10-01 10:00:00', 'video_id' => 12, 'score' => null, 'max_score' => 0,
            'scaled_result' => 100, 'percentage_to_succeed' => null, 'passed' => true, 'session_started_at' => null, 'video_closed_at' => null,
            'has_started' => false, 'has_finished' => false, 'variables' => []]]));

        $filter = new StatisticsFilter(from: CarbonImmutable::parse('2026-10-01'), until: CarbonImmutable::parse('2026-10-31'), variables: ['team-a']);
        $session = $this->hihaho->video(12)->sessions()->list($filter, PerPage::Ten)->page()->items[0];

        $this->assertRequested('GET', 'v2/video/12/session?start_date=2026-10-01%2000%3A00%3A00&end_date=2026-10-31%2000%3A00%3A00&variables%5B0%5D=team-a&per_page=10&page=1');
        $this->assertEqualsWithDelta(100.0, $session->scaledResult, PHP_FLOAT_EPSILON);
    }

    #[Test]
    public function a_session_detail_carries_answers_and_rubric_scores(): void
    {
        $this->respond(['data' => ['id' => 1, 'video_id' => 12, 'max_score' => 2, 'scaled_result' => 50.5, 'passed' => false,
            'last_reached_point_seconds' => 30.2, 'questions' => [['id' => 4, 'text' => 'Why?', 'question_time' => 12.5, 'max_points' => 2,
                'scored_points' => 1, 'answer_id' => [3, 4], 'answer_text' => 'Because', 'shown_at' => '2026-10-01 10:00:00.120',
                'rubric_scores' => [['criterion' => 'Clear', 'score' => 0.5, 'passed' => true, 'pass_threshold' => 0.4, 'match_status' => 'matched']],
                'retries' => [['answer_text' => 'First try', 'is_correct_answer' => false, 'rubric_scores' => null]]]]]]);

        $detail = $this->hihaho->video(12)->sessions()->get(1);
        $question = $detail->questions[0];

        $this->assertSame(RubricMatchStatus::Matched, $question->rubricScores[0]->matchStatus);
        $this->assertSame('First try', $question->retries[0]->answerText);
        $this->assertSame([], $question->retries[0]->rubricScores);
        $this->assertSame([3, 4], $question->raw()['answer_id']);
        $this->assertSame('120', $question->shownAt?->format('v'));
    }

    #[Test]
    #[DataProvider('halfDateRangeProvider')]
    public function a_filter_with_only_one_date_is_refused(?CarbonImmutable $from, ?CarbonImmutable $until): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StatisticsFilter(from: $from, until: $until);
    }

    /**
     * @return iterable<string, array{CarbonImmutable|null, CarbonImmutable|null}>
     */
    public static function halfDateRangeProvider(): iterable
    {
        yield 'only from' => [CarbonImmutable::parse('2026-10-01'), null];
        yield 'only until' => [null, CarbonImmutable::parse('2026-10-31')];
    }

    #[Test]
    public function views_without_grouping_return_one_row(): void
    {
        $this->respond(['data' => ['total' => 10, 'started' => 8, 'finished' => 5, 'passed' => 4, 'has_result' => 5, 'failed' => 1, 'unfinished' => 5]]);

        $views = $this->hihaho->video(12)->statistics()->views();

        $this->assertCount(1, $views);
        $this->assertSame(10, $views[0]->total);
        $this->assertNull($views[0]->period);
    }

    #[Test]
    public function grouped_views_return_one_row_per_period(): void
    {
        $this->respond(['data' => [
            ['period' => '2026-W40', 'start_date' => '2026-09-28 00:00:00', 'end_date' => '2026-10-04 23:59:59', 'total' => 3],
            ['period' => '2026-W41', 'start_date' => '2026-10-05 00:00:00', 'end_date' => '2026-10-11 23:59:59', 'total' => 4],
        ]]);

        $filter = new StatisticsFilter(from: CarbonImmutable::parse('2026-09-28'), until: CarbonImmutable::parse('2026-10-11'), groupBy: GroupByPeriod::Week);
        $views = $this->hihaho->video(12)->statistics()->views($filter);

        $this->assertSame(['2026-W40', '2026-W41'], array_map(fn (ViewStatistics $row) => $row->period, $views));
    }

    #[Test]
    public function views_by_user_agent_keep_the_unknown_bucket(): void
    {
        $this->respond(['data' => ['unknown' => null, 'Mozilla/5.0' => 7]]);

        $this->assertSame(['unknown' => 0, 'Mozilla/5.0' => 7], $this->hihaho->video(12)->statistics()->viewsByUserAgent());
    }

    #[Test]
    public function question_statistics_read_the_rating_only_field(): void
    {
        $this->respond(['data' => [['id' => 4, 'question_text' => 'Rate it', 'question_type' => 'rating', 'video_time' => 5, 'max_points' => 0,
            'total_given_answers' => 2, 'average_answer_time_seconds' => 3, 'average_rating' => 4, 'answers' => [['label' => '5', 'answered_count' => 1, 'is_correct_answer' => false]]]]]);

        $statistic = $this->hihaho->video(12)->statistics()->questions()->page()->items[0];

        $this->assertEqualsWithDelta(4.0, $statistic->averageRating, PHP_FLOAT_EPSILON);
        $this->assertSame(1, $statistic->answers[0]->answeredCount);
    }

    #[Test]
    public function session_metadata_reads_unix_timestamps(): void
    {
        $this->respond(['data' => ['id' => 1, 'email' => null, 'is_embedded' => true, 'started_at' => 1792073391, 'closed_at' => null]]);

        $metadata = $this->hihaho->video(12)->statistics()->sessionMetadata(1);

        $this->assertSame('2026-10-15 14:09:51', $metadata->startedAt?->format('Y-m-d H:i:s'));
        $this->assertNull($metadata->closedAt);
    }

    #[Test]
    public function aggregated_statistics_off_throws(): void
    {
        $this->respond(['message' => 'Aggregated statistics are disabled.', 'success' => false], 412);

        $this->expectException(PreconditionFailedException::class);

        $this->hihaho->video(12)->statistics()->aggregated();
    }

    #[Test]
    public function aggregated_statistics_are_read(): void
    {
        $this->respond(['data' => ['id' => 12, 'uuid' => 'abc', 'status' => 3, 'video_container_id' => 7, 'created_at' => '2026-10-15 14:09:51',
            'aggregated_statistics' => ['aggregated_at' => '2026-10-15 14:00:00', 'views' => 10, 'total_allowed_viewers' => 0,
                'interactions' => ['count' => 1, 'total_clicks' => 3, 'details' => [['id' => 5, 'title' => 'Go', 'type' => 'link', 'start_time' => 1, 'total_clicks' => 3]]],
                'questions' => ['count' => 1, 'total_given_answers' => 4, 'total_given_correct_answers' => 2, 'details' => []]]]]);

        $statistics = $this->hihaho->video(12)->statistics()->aggregated();

        $this->assertSame(10, $statistics->aggregatedStatistics->views);
        $this->assertSame(InteractionType::Link, $statistics->aggregatedStatistics->interactions->details[0]->type);
    }

    #[Test]
    public function the_export_keeps_the_import_shape(): void
    {
        $this->respond(['data' => ['interactions' => [['type' => 'text', 'start_time' => 1]], 'questions' => [], 'subtitles' => [], 'interaction_groups' => [], 'question_groups' => []]]);

        $export = $this->hihaho->video(12)->export();

        $this->assertSame([['type' => 'text', 'start_time' => 1]], $export->interactions);
        $this->assertSame([], $export->adaptiveLearningSubjects);
    }

    /**
     * @return array<string, mixed>
     */
    private function aiTaskRow(int $id): array
    {
        return ['id' => $id, 'task_type' => 'generate_chapters', 'target_locale' => null, 'status' => 'running',
            'created_at' => '2026-10-15T14:09:51.000000Z', 'started_progressing_at' => '2026-10-15T14:10:00.000000Z', 'completed_at' => null, 'failed_at' => null];
    }
}
