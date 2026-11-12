<?php declare(strict_types=1);

namespace Hihaho\Sdk\Exceptions;

/**
 * 400 from the AI task endpoint: the task was not queued.
 */
final class AiTaskRefusedException extends HttpException
{
    /**
     * @return list<array<array-key, mixed>>
     */
    public function tasks(): array
    {
        $tasks = $this->body()['tasks'] ?? null;

        return is_array($tasks) ? array_values(array_filter($tasks, is_array(...))) : [];
    }
}
