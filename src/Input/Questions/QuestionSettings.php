<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

use Hihaho\Sdk\Enums\FeedbackType;
use Hihaho\Sdk\Enums\ImageFit;
use Hihaho\Sdk\Enums\Layout;
use Hihaho\Sdk\Enums\QuestionDifficulty;
use Hihaho\Sdk\Enums\VideoFontSize;
use Hihaho\Sdk\Input\Fields;

/**
 * Settings every question type accepts. A setting left null keeps the server default.
 * Image and sound URLs must point at hihaho storage of a user who shares a folder with you.
 */
final readonly class QuestionSettings
{
    /**
     * @param  list<EvaluationRubric>  $evaluationRubrics
     */
    public function __construct(
        public ?int $points = null,
        public ?bool $countsPointsPerAnswer = null,
        public ?Layout $layout = null,
        public ?VideoFontSize $fontSize = null,
        public ?FeedbackType $feedbackType = null,
        public ?FeedbackTitle $feedbackTitle = null,
        public ?bool $feedbackScreenColor = null,
        public ?string $correctAnswerFeedback = null,
        public ?string $incorrectAnswerFeedback = null,
        public ?string $generalFeedback = null,
        public ?bool $enablePartlyCorrectFeedback = null,
        public ?string $partlyCorrectFeedback = null,
        public ?bool $feedbackWhenAnswered = null,
        public ?bool $feedbackOnlyOnScoreScreen = null,
        public ?string $imageUrl = null,
        public ?ImageFit $imageFit = null,
        public ?string $feedbackImageUrl = null,
        public ?ImageFit $feedbackImageFit = null,
        public ?string $soundUrl = null,
        public ?bool $playSound = null,
        public ?bool $fadeIn = null,
        public ?bool $isRepeatable = null,
        public ?bool $retryOnIncorrectAnswer = null,
        public ?bool $submitInstantly = null,
        public ?bool $shuffleAnswers = null,
        public ?int $answerMinimumCharacterCount = null,
        public ?string $submitLabel = null,
        public ?string $subtitleText = null,
        public ?string $additionalInformation = null,
        public ?bool $hideFromPdfExport = null,
        public ?QuestionActions $actions = null,
        public ?Markup $markup = null,
        public array $evaluationRubrics = [],
        public ?QuestionDifficulty $difficulty = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return Fields::build([
            'points' => $this->points,
            'counts_points_per_answer' => $this->countsPointsPerAnswer,
            'layout' => $this->layout?->value,
            'font_size' => $this->fontSize?->value,
            'feedback_type' => $this->feedbackType?->value,
            'feedback_screen_color' => $this->feedbackScreenColor,
            'feedback_screen_correct_answer_feedback' => $this->correctAnswerFeedback,
            'feedback_screen_incorrect_answer_feedback' => $this->incorrectAnswerFeedback,
            'feedback_screen_general_feedback' => $this->generalFeedback,
            'enable_partly_correct_feedback' => $this->enablePartlyCorrectFeedback,
            'explanation_partly_correct' => $this->partlyCorrectFeedback,
            'should_show_feedback_when_answered' => $this->feedbackWhenAnswered,
            'should_show_feedback_only_on_score_screen' => $this->feedbackOnlyOnScoreScreen,
            'image_url' => $this->imageUrl,
            'image_fit' => $this->imageFit?->value,
            'feedback_image_url' => $this->feedbackImageUrl,
            'feedback_image_fit' => $this->feedbackImageFit?->value,
            'sound_url' => $this->soundUrl,
            'should_play_sound' => $this->playSound,
            'should_fade_in' => $this->fadeIn,
            'is_repeatable' => $this->isRepeatable,
            'should_retry_question_on_incorrect_answer' => $this->retryOnIncorrectAnswer,
            'should_submit_instantly' => $this->submitInstantly,
            'should_shuffle_answers' => $this->shuffleAnswers,
            'answer_minimum_character_count' => $this->answerMinimumCharacterCount,
            'submit_label' => $this->submitLabel,
            'subtitle_text' => $this->subtitleText,
            'additional_information' => $this->additionalInformation,
            'hide_from_pdf_export' => $this->hideFromPdfExport,
            'action' => $this->actions?->toArray(),
            'markup' => $this->markup?->toArray(),
            'difficulty' => $this->difficulty?->value,
            'evaluation_rubrics' => array_map(fn (EvaluationRubric $rubric): array => $rubric->toArray(), $this->evaluationRubrics),
        ], [$this->feedbackTitle?->toArray() ?? []]);
    }
}
