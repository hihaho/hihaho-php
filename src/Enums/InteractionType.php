<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

enum InteractionType: string
{
    case Chapter = 'chapter';
    case Frame = 'frame';
    case Glossary = 'glossary';
    case Highlight = 'highlight';
    case Hotspot = 'hotspot';
    case Image = 'image';
    case JumpTo = 'jump_to';
    case Link = 'link';
    case LinkVideo = 'link_video';
    case Menu = 'menu';
    case Pause = 'pause';
    case Scroll = 'scroll';
    case Sound = 'sound';
    case Summary = 'summary';
    case Text = 'text';
    case Zoom = 'zoom';
}
