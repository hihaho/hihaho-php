<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * What happens after an answer. Different from InteractionActionType.
 *
 * The API takes the lowercase case names.
 */
enum AnswerActionType: string
{
    case ContinuePlaying = 'continue_playing';
    case RepeatQuestion = 'repeat_question';
    case Jump = 'jump';
    case JumpToStart = 'jump_to_start';
    case JumpToEnd = 'jump_to_end';
    case JumpToInteraction = 'jump_to_interaction';
    case Link = 'link';
    case ShowScoreScreen = 'show_score_screen';
    case LinkVideo = 'link_video';
    case JumpToChapter = 'jump_to_chapter';
}
