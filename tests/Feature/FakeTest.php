<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Feature;

use Hihaho\Sdk\Enums\Locale;
use Hihaho\Sdk\Facades\Hihaho;
use Hihaho\Sdk\HihahoServiceProvider;
use Hihaho\Sdk\Input\Interactions\TextInteraction;
use Hihaho\Sdk\Input\Interactions\ZoomInteraction;
use Hihaho\Sdk\Input\Questions\EssayQuestion;
use Hihaho\Sdk\Input\SubtitleInput;
use Hihaho\Sdk\Input\VideoUpdate;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;

final class FakeTest extends TestCase
{
    #[Test]
    public function a_batch_create_returns_one_id_per_item_without_setup(): void
    {
        Hihaho::fake();

        $this->assertSame([1, 2], Hihaho::video(12)->interactions()->createMany([new ZoomInteraction(1, 2), new ZoomInteraction(3, 4)]));
        $this->assertSame([1], Hihaho::video(12)->questions()->createMany([new EssayQuestion(5, 'Why?')]));
    }

    #[Test]
    public function a_pattern_of_the_caller_wins_over_the_default_ids(): void
    {
        Hihaho::fake(['v2/video/12/interaction/batch' => Http::response(['ids' => [41]], 201)]);

        $this->assertSame([41], Hihaho::video(12)->interactions()->createMany([new ZoomInteraction(1, 2)]));
    }

    #[Test]
    public function a_batch_delete_is_not_answered_with_ids(): void
    {
        Hihaho::fake();

        Hihaho::video(12)->interactions()->deleteMany([4]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE');
    }

    #[Test]
    public function it_asserts_the_created_items(): void
    {
        Hihaho::fake();

        Hihaho::video(12)->interactions()->createMany([new TextInteraction(5, 10, text: 'Watch this')]);
        Hihaho::video(12)->subtitles()->createMany([new SubtitleInput('English', 'en', 'WEBVTT')]);

        Hihaho::assertCreatedInteractions(12);
        Hihaho::assertCreatedInteractions(12, fn (array $items): bool => $items[0]['text'] === 'Watch this');
        Hihaho::assertCreatedSubtitles(12, fn (array $items): bool => $items[0]['language'] === 'en');
    }

    #[Test]
    public function an_assertion_fails_for_another_video_or_other_items(): void
    {
        Hihaho::fake();

        Hihaho::video(12)->questions()->createMany([new EssayQuestion(5, 'Why?')]);

        $this->assertFails(fn () => Hihaho::assertCreatedQuestions(13), 'No questions were created for video 13.');
        $this->assertFails(fn () => Hihaho::assertCreatedQuestions(12, fn (array $items): bool => count($items) === 2), 'No questions were created for video 12.');
        $this->assertFails(fn () => Hihaho::assertCreatedInteractions(12), 'No interactions were created for video 12.');
    }

    #[Test]
    public function it_asserts_the_updated_fields(): void
    {
        Hihaho::fake(['v2/video/12' => Http::response(['data' => ['id' => 12]])]);

        Hihaho::video(12)->update(VideoUpdate::make()
            ->displayName('Safety basics')
            ->locale(Locale::NlNL));

        Hihaho::assertUpdatedVideo(12, fn (array $fields): bool => $fields === ['display_name' => 'Safety basics', 'locale' => 'nl_NL']);
        $this->assertFails(fn () => Hihaho::assertUpdatedVideo(12, fn (array $fields): bool => isset($fields['status'])), 'Video 12 was not updated as expected.');
    }

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [HihahoServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app->make(Repository::class)->set('hihaho.token', 'test-token');
    }

    /**
     * @param  non-empty-string  $message
     */
    private function assertFails(callable $assertion, string $message): void
    {
        try {
            $assertion();
        } catch (ExpectationFailedException $expectationFailedException) {
            $this->assertStringStartsWith($message, $expectationFailedException->getMessage());

            return;
        }

        self::fail('The assertion passed.');
    }
}
