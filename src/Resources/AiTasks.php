<?php declare(strict_types=1);

namespace Hihaho\Sdk\Resources;

use Hihaho\Sdk\Data\AiTask;
use Hihaho\Sdk\Data\AiTaskStarted;
use Hihaho\Sdk\Enums\AiTaskType;
use Hihaho\Sdk\Enums\Locale;
use Hihaho\Sdk\Http\Connector;
use Hihaho\Sdk\Input\AiTaskOptions;
use Hihaho\Sdk\Pagination\ListRequest;
use InvalidArgumentException;

final readonly class AiTasks extends Resource
{
    public function __construct(Connector $connector, private int $videoId)
    {
        parent::__construct($connector);
    }

    /**
     * Newest first, 10 per page.
     *
     * @return ListRequest<AiTask>
     */
    public function list(): ListRequest
    {
        return $this->paged("v2/video/{$this->videoId}/ai-task", [], AiTask::fromArray(...), fn (AiTask $task): int => $task->id);
    }

    /**
     * A missing transcript is queued first. Throws an AI-task-refused exception (with the latest
     * tasks) when the task cannot start.
     */
    public function start(AiTaskType $type, ?Locale $targetLocale = null, AiTaskOptions $options = new AiTaskOptions()): AiTaskStarted
    {
        if (! in_array($type, AiTaskType::startable(), true)) {
            throw new InvalidArgumentException("Task type {$type->value} cannot be started through the API.");
        }

        if ($type->needsTargetLocale() && ! $targetLocale instanceof Locale) {
            throw new InvalidArgumentException("Task type {$type->value} needs a target locale.");
        }

        $userInput = $options->toArray();
        $body = array_filter([
            'task_type' => $type->value,
            'target_locale' => $targetLocale?->value,
            'user_input' => $userInput === [] ? null : $userInput,
        ], fn (mixed $value): bool => $value !== null);

        return AiTaskStarted::fromArray($this->connector->decode($this->connector->post("v2/video/{$this->videoId}/ai-task", $body)));
    }
}
