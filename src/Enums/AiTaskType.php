<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Only some can be started through the API; see `startable()`.
 */
enum AiTaskType: string
{
    case Transcribe = 'transcribe';
    case TranscribeNonVideoLanguage = 'transcribe_non_video_language';
    case TranslateSubtitles = 'translate_subtitles';
    case GenerateOpenQuestions = 'generate_open_questions';
    case GenerateChapters = 'generate_chapters';
    case GenerateSummary = 'generate_summary';
    case GenerateGlossary = 'generate_glossary';
    case GenerateEvaluationRubrics = 'generate_evaluation_rubrics';
    case AnalyzeQuestionPerformance = 'analyze_question_performance';
    case GenerateInteractionsFromScript = 'generate_interactions_from_script';
    case GenerateVideoTitle = 'generate_video_title';
    case TranslateImportedInteractions = 'translate_imported_interactions';
    case TextToSpeech = 'text_to_speech';
    case DubAudio = 'dub_audio';

    /**
     * The task types the API accepts when starting a task.
     *
     * @return list<self>
     */
    public static function startable(): array
    {
        return [
            self::Transcribe,
            self::TranscribeNonVideoLanguage,
            self::TranslateSubtitles,
            self::GenerateOpenQuestions,
            self::GenerateChapters,
            self::GenerateEvaluationRubrics,
            self::AnalyzeQuestionPerformance,
        ];
    }

    public function needsTargetLocale(): bool
    {
        return $this === self::TranscribeNonVideoLanguage || $this === self::TranslateSubtitles;
    }
}
