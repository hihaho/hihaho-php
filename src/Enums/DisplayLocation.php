<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Where a menu or chapter button shows.
 *
 * The API takes the lowercase case names.
 */
enum DisplayLocation: string
{
    case PlayerBarAndVideo = 'player_bar_and_video';
    case PlayerBar = 'player_bar';
    case Video = 'video';
}
