<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Where a video may be embedded.
 */
enum Embedding: string
{
    case DoNotAllow = 'do_not_allow';
    case AllWebsites = 'all_websites';
    case MyWebsites = 'my_websites';
}
