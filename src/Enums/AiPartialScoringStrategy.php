<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * How an AI-judged answer earns partial points.
 */
enum AiPartialScoringStrategy: string
{
    case Average = 'average';
    case RubricPassCount = 'rubric_pass_count';
}
