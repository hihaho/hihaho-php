<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Unit\Input;

use Closure;
use Hihaho\Sdk\Enums\AiPartialScoringStrategy;
use Hihaho\Sdk\Enums\AiReviewStrictness;
use Hihaho\Sdk\Enums\FeedbackType;
use Hihaho\Sdk\Enums\ImageFit;
use Hihaho\Sdk\Enums\Layout;
use Hihaho\Sdk\Enums\QuestionDifficulty;
use Hihaho\Sdk\Enums\VideoFontSize;
use Hihaho\Sdk\Input\Questions\Answer;
use Hihaho\Sdk\Input\Questions\AnswerAction;
use Hihaho\Sdk\Input\Questions\EssayQuestion;
use Hihaho\Sdk\Input\Questions\EvaluationRubric;
use Hihaho\Sdk\Input\Questions\FeedbackTitle;
use Hihaho\Sdk\Input\Questions\ImageQuestion;
use Hihaho\Sdk\Input\Questions\Markup;
use Hihaho\Sdk\Input\Questions\MarkupButton;
use Hihaho\Sdk\Input\Questions\MultipleChoiceQuestion;
use Hihaho\Sdk\Input\Questions\MultipleResponseQuestion;
use Hihaho\Sdk\Input\Questions\OpenQuestion;
use Hihaho\Sdk\Input\Questions\QuestionActions;
use Hihaho\Sdk\Input\Questions\QuestionInput;
use Hihaho\Sdk\Input\Questions\QuestionSettings;
use Hihaho\Sdk\Input\Questions\RatingQuestion;
use Hihaho\Sdk\Input\Questions\VacancyQuestion;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The allowed keys are the keys the API's question import accepts, copied by hand, not from
 * SDK output: a key outside them is dropped by the API without an error. The adaptive fields
 * are left out on purpose.
 */
final class QuestionInputTest extends TestCase
{
    private const array COMMON = [
        'type', 'active_at', 'text', 'answer_minimum_character_count', 'counts_points_per_answer', 'can_select_multiple_answers',
        'enable_partly_correct_feedback', 'explanation_partly_correct', 'feedback_type', 'feedback_screen_color',
        'feedback_screen_correct_answer_feedback', 'feedback_screen_general_feedback', 'feedback_screen_incorrect_answer_feedback',
        'font_size', 'hide_from_pdf_export', 'image_fit', 'image_url', 'feedback_image_fit', 'feedback_image_url', 'is_repeatable',
        'layout', 'points', 'feedback_title_type', 'feedback_title_custom_text', 'should_fade_in', 'should_play_sound',
        'should_retry_question_on_incorrect_answer', 'should_submit_instantly', 'should_show_feedback_only_on_score_screen',
        'should_shuffle_answers', 'sound_url', 'submit_label', 'subtitle_text', 'additional_information',
        'should_show_feedback_when_answered', 'action', 'markup', 'evaluation_rubrics', 'ai_context_documents', 'ref', 'difficulty',
    ];

    private const array MARKUP = [
        'markup.placeholder', 'markup.prefix', 'markup.suffix',
        'markup.back_button.is_enabled', 'markup.back_button.name', 'markup.back_button.action.type', 'markup.back_button.action.link',
        'markup.back_button.action.time', 'markup.back_button.action.question_id', 'markup.back_button.action.interaction_id', 'markup.back_button.action.target_ref',
        'markup.feedback_action_button.is_enabled', 'markup.feedback_action_button.name', 'markup.feedback_action_button.action.type',
        'markup.feedback_action_button.action.link', 'markup.feedback_action_button.action.time',
        'markup.feedback_action_button.action.question_id', 'markup.feedback_action_button.action.interaction_id', 'markup.feedback_action_button.action.target_ref',
    ];

    private const array VACANCY = [
        'markup.vacancy.receiver_email_address', 'markup.vacancy.company_name_label', 'markup.vacancy.first_name_label',
        'markup.vacancy.last_name_label', 'markup.vacancy.upload_instructional_placeholder', 'markup.vacancy.should_allow_file_upload',
    ];

    private const array OPEN = ['should_be_judged_via_ai', 'ai_coaching_mode', 'ai_partial_scoring_strategy', 'use_transcript', 'use_global_knowledge', 'overrides_ai_context_documents', 'review_strictness'];

    private const array SETTINGS_SENT = [
        'type', 'active_at', 'text', 'points', 'counts_points_per_answer', 'layout', 'font_size', 'feedback_type', 'feedback_title_type',
        'feedback_screen_color', 'feedback_screen_correct_answer_feedback', 'feedback_screen_incorrect_answer_feedback',
        'feedback_screen_general_feedback', 'enable_partly_correct_feedback', 'explanation_partly_correct',
        'should_show_feedback_when_answered', 'should_show_feedback_only_on_score_screen', 'image_url', 'image_fit',
        'feedback_image_url', 'feedback_image_fit', 'sound_url', 'should_play_sound', 'should_fade_in', 'is_repeatable',
        'should_retry_question_on_incorrect_answer', 'should_submit_instantly', 'should_shuffle_answers',
        'answer_minimum_character_count', 'submit_label', 'subtitle_text', 'additional_information', 'hide_from_pdf_export',
        'action', 'evaluation_rubrics', 'difficulty', 'markup.placeholder', 'markup.prefix', 'markup.suffix',
        'markup.back_button.is_enabled', 'markup.back_button.name', 'markup.back_button.action.type',
        'markup.feedback_action_button.is_enabled', 'markup.feedback_action_button.name', 'markup.feedback_action_button.action.type',
    ];

    /**
     * @param  list<string>  $typeAllowed
     * @param  list<string>  $expectedSent
     */
    #[Test]
    #[DataProvider('typeProvider')]
    public function each_type_sends_only_keys_the_api_keeps_for_it(QuestionInput $question, string $type, array $typeAllowed, array $expectedSent): void
    {
        $body = $question->toArray();
        $sent = [...array_keys($body), ...self::markupPaths($body['markup'] ?? [])];

        $this->assertSame($type, $body['type']);
        $this->assertSame([], array_values(array_diff($sent, [...self::COMMON, ...self::MARKUP, ...$typeAllowed])), 'Sent keys the API drops for this type.');
        $this->assertSame([], array_values(array_diff([...self::SETTINGS_SENT, ...$expectedSent], $sent)), 'Set fields that were not sent.');
    }

    /**
     * @return iterable<string, array{QuestionInput, string, list<string>, list<string>}>
     */
    public static function typeProvider(): iterable
    {
        $answers = [Answer::correct('Yes'), Answer::wrong('No', AnswerAction::repeatQuestion(), 'Try again', 0)];
        $settings = self::settings();

        yield 'essay' => [new EssayQuestion(10, 'Why?', $settings), 'essay', [], []];
        yield 'rating' => [new RatingQuestion(10, 'Rate it', $settings), 'rating', [], []];
        yield 'multiple choice' => [new MultipleChoiceQuestion(10, 'Pick', $answers, $settings), 'mc', ['answers'], ['answers']];
        yield 'multiple response' => [new MultipleResponseQuestion(10, 'Pick', $answers, $settings), 'mr', ['answers'], ['answers']];
        yield 'image' => [new ImageQuestion(10, 'Pick', $answers, true, $settings, true), 'image', ['answers', 'enable_answer_image_zoom'], ['answers', 'can_select_multiple_answers', 'enable_answer_image_zoom']];
        yield 'open' => [new OpenQuestion(10, 'Explain', $answers, true, true, AiPartialScoringStrategy::Average, true, false, [['id' => 1, 'folderId' => 7]], $settings, AiReviewStrictness::Strict), 'open',
            ['answers', ...self::OPEN], ['answers', ...self::OPEN, 'ai_context_documents']];
        yield 'vacancy' => [new VacancyQuestion(10, 'jobs@hihaho.com', 'Apply', 'Company', 'First', 'Last', 'Upload your CV', true, $settings), 'vacancy', self::VACANCY, self::VACANCY];
    }

    #[Test]
    public function a_multiple_choice_question_body(): void
    {
        $question = new MultipleChoiceQuestion(
            activeAt: 12.5,
            text: 'Which one?',
            answers: [new Answer('A', true, AnswerAction::jump(20)), new Answer('B', false)],
            settings: new QuestionSettings(points: 2, feedbackType: FeedbackType::Screen, actions: new QuestionActions(incorrect: AnswerAction::jumpToInteraction(5))),
        );

        $this->assertEquals([
            'type' => 'mc',
            'active_at' => 12.5,
            'text' => 'Which one?',
            'points' => 2,
            'feedback_type' => 'screen',
            'action' => ['incorrect' => ['type' => 'jump_to_interaction', 'interaction_id' => 5]],
            'answers' => [
                ['text' => 'A', 'is_correct' => true, 'action' => ['type' => 'jump', 'time' => 20]],
                ['text' => 'B', 'is_correct' => false, 'action' => ['type' => 'continue_playing']],
            ],
        ], $question->toArray());
    }

    #[Test]
    public function difficulty_strictness_and_zoom_are_sent_as_the_api_values(): void
    {
        $open = new OpenQuestion(1, settings: new QuestionSettings(difficulty: QuestionDifficulty::Medium), reviewStrictness: AiReviewStrictness::VeryLenient)->toArray();
        $image = new ImageQuestion(1, enableAnswerImageZoom: false)->toArray();

        $this->assertSame('medium', $open['difficulty']);
        $this->assertSame(1, $open['review_strictness']);
        $this->assertFalse($image['enable_answer_image_zoom']);
    }

    #[Test]
    public function refs_link_an_action_to_a_question_in_the_same_request(): void
    {
        $jump = AnswerAction::jumpToQuestionRef('q2');

        $body = new MultipleChoiceQuestion(
            activeAt: 1,
            answers: [new Answer('A', true, $jump)],
            settings: new QuestionSettings(actions: new QuestionActions(correct: $jump), markup: new Markup(backButton: new MarkupButton('Back', $jump))),
            ref: 'q1',
        )->toArray();

        $expected = ['type' => 'jump_to_interaction', 'target_ref' => 'q2'];
        $this->assertSame('q1', $body['ref']);
        $this->assertSame([['text' => 'A', 'is_correct' => true, 'action' => $expected]], $body['answers']);
        $this->assertSame(['correct' => $expected], $body['action']);
        $this->assertSame(['back_button' => ['is_enabled' => true, 'name' => 'Back', 'action' => $expected]], $body['markup']);
    }

    /**
     * @param  Closure(string): QuestionInput  $make
     */
    #[Test]
    #[DataProvider('refProvider')]
    public function every_question_type_sends_its_ref(Closure $make): void
    {
        $this->assertSame('mine', $make('mine')->toArray()['ref']);
    }

    /**
     * @return iterable<string, array{Closure(string): QuestionInput}>
     */
    public static function refProvider(): iterable
    {
        yield 'essay' => [fn (string $ref): QuestionInput => new EssayQuestion(1, ref: $ref)];
        yield 'rating' => [fn (string $ref): QuestionInput => new RatingQuestion(1, ref: $ref)];
        yield 'multiple choice' => [fn (string $ref): QuestionInput => new MultipleChoiceQuestion(1, ref: $ref)];
        yield 'multiple response' => [fn (string $ref): QuestionInput => new MultipleResponseQuestion(1, ref: $ref)];
        yield 'image' => [fn (string $ref): QuestionInput => new ImageQuestion(1, ref: $ref)];
        yield 'open' => [fn (string $ref): QuestionInput => new OpenQuestion(1, ref: $ref)];
        yield 'vacancy' => [fn (string $ref): QuestionInput => new VacancyQuestion(1, 'jobs@hihaho.com', ref: $ref)];
    }

    #[Test]
    public function a_vacancy_question_carries_the_receiver(): void
    {
        $body = new VacancyQuestion(activeAt: 3, receiverEmailAddress: 'jobs@hihaho.com', settings: new QuestionSettings(markup: new Markup(placeholder: 'Your answer')))->toArray();

        $this->assertSame(['placeholder' => 'Your answer', 'vacancy' => ['receiver_email_address' => 'jobs@hihaho.com']], $body['markup']);
    }

    #[Test]
    public function a_custom_feedback_title_sends_the_type_and_texts(): void
    {
        $body = new EssayQuestion(1, settings: new QuestionSettings(feedbackTitle: FeedbackTitle::custom(correct: 'Well done', partlyCorrect: 'Almost')))->toArray();

        $this->assertSame('custom', $body['feedback_title_type']);
        $this->assertSame(['correct' => 'Well done', 'partly_correct' => 'Almost'], $body['feedback_title_custom_text']);
    }

    /**
     * @param  Closure(): AnswerAction  $action
     */
    #[Test]
    #[DataProvider('buttonWithoutEffectProvider')]
    public function an_enabled_button_that_only_continues_or_repeats_is_refused(Closure $action): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('saves switched off');

        new MarkupButton('Back', $action());
    }

    /**
     * @return iterable<string, array{Closure(): AnswerAction}>
     */
    public static function buttonWithoutEffectProvider(): iterable
    {
        yield 'continue playing' => [AnswerAction::continuePlaying(...)];
        yield 'repeat question' => [AnswerAction::repeatQuestion(...)];
    }

    #[Test]
    public function a_switched_off_button_may_only_continue(): void
    {
        $this->assertFalse(new MarkupButton('Back', AnswerAction::continuePlaying(), isEnabled: false)->toArray()['is_enabled']);
    }

    /**
     * @param  Closure(list<Answer>): QuestionInput  $make
     */
    #[Test]
    #[DataProvider('answerCapProvider')]
    public function twenty_answers_are_accepted_and_twenty_one_refused(Closure $make): void
    {
        $answer = new Answer('x', false);

        $answers = $make(array_fill(0, 20, $answer))->toArray()['answers'];
        $this->assertIsArray($answers);
        $this->assertCount(20, $answers);

        $this->expectException(InvalidArgumentException::class);

        $make(array_fill(0, 21, $answer));
    }

    /**
     * @return iterable<string, array{Closure(list<Answer>): QuestionInput}>
     */
    public static function answerCapProvider(): iterable
    {
        yield 'multiple choice' => [fn (array $answers): QuestionInput => new MultipleChoiceQuestion(1, answers: $answers)];
        yield 'multiple response' => [fn (array $answers): QuestionInput => new MultipleResponseQuestion(1, answers: $answers)];
        yield 'image' => [fn (array $answers): QuestionInput => new ImageQuestion(1, answers: $answers)];
        yield 'open' => [fn (array $answers): QuestionInput => new OpenQuestion(1, answers: $answers)];
    }

    /**
     * @return list<string>
     */
    private static function markupPaths(mixed $value, string $path = 'markup'): array
    {
        if (! is_array($value) || $value === [] || array_is_list($value)) {
            return $path === 'markup' ? [] : [$path];
        }

        $paths = [];

        foreach ($value as $key => $child) {
            $paths = [...$paths, ...self::markupPaths($child, "{$path}.{$key}")];
        }

        return $paths;
    }

    private static function settings(): QuestionSettings
    {
        $button = new MarkupButton('Back', AnswerAction::jumpToStart());

        return new QuestionSettings(
            points: 1, countsPointsPerAnswer: true, layout: Layout::Modern, fontSize: VideoFontSize::Pt20, feedbackType: FeedbackType::Direct,
            feedbackTitle: FeedbackTitle::automatic(), feedbackScreenColor: true, correctAnswerFeedback: 'c', incorrectAnswerFeedback: 'i', generalFeedback: 'g',
            enablePartlyCorrectFeedback: true, partlyCorrectFeedback: 'p', feedbackWhenAnswered: true, feedbackOnlyOnScoreScreen: false,
            imageUrl: '/img/default_image_interaction.png', imageFit: ImageFit::Cover, feedbackImageUrl: '/img/default_image_interaction.png', feedbackImageFit: ImageFit::Contain,
            soundUrl: '/sound/no_audio_sound.mp3', playSound: true, fadeIn: true, isRepeatable: true, retryOnIncorrectAnswer: true, submitInstantly: false,
            shuffleAnswers: true, answerMinimumCharacterCount: 10, submitLabel: 'Send', subtitleText: 'Sub', additionalInformation: 'Info', hideFromPdfExport: false,
            actions: new QuestionActions(AnswerAction::continuePlaying(), AnswerAction::showScoreScreen(), AnswerAction::link('https://example.com')),
            markup: new Markup('Type here', 'pre', 'post', $button, $button),
            evaluationRubrics: [new EvaluationRubric('Clear', 'The answer is clear')],
            difficulty: QuestionDifficulty::Hard,
        );
    }
}
