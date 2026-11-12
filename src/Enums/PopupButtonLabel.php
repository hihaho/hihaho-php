<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Label of the button that opens a popup interaction.
 */
enum PopupButtonLabel: string
{
    case DefaultText = 'default_text';
    case CustomText = 'custom_text';
    case EyeIcon = 'eye_icon';
    case InfoIcon = 'info_icon';
    case QuestionIcon = 'question_icon';
}
