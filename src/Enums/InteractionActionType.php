<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * What an interaction does when clicked. Different from AnswerActionType.
 *
 * The API takes the lowercase case names.
 */
enum InteractionActionType: string
{
    case NoAction = 'no_action';
    case Link = 'link';
    case Jump = 'jump';
    case JumpToStart = 'jump_to_start';
    case JumpToEnd = 'jump_to_end';
    case JumpToInteraction = 'jump_to_interaction';
    case ShowScoreScreen = 'show_score_screen';
    case LinkVideo = 'link_video';
    case DelayedJump = 'delayed_jump';
    case JumpToChapter = 'jump_to_chapter';
    case EnableSubtitle = 'enable_subtitle';
    case ToggleSound = 'toggle_sound';
}
