<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

enum AiTaskStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Failed = 'failed';
    case Succeeded = 'succeeded';
}
