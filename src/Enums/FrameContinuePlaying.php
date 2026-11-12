<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * How a viewer resumes a frame that paused the video.
 *
 * The API takes the lowercase case names.
 */
enum FrameContinuePlaying: string
{
    case ClickOutsideFrameElement = 'click_outside_frame_element';
    case ClickOutsideFrameElementOrFrameButton = 'click_outside_frame_element_or_frame_button';
}
