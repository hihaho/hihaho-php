<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use Hihaho\Sdk\Support\Payload;

/**
 * The answer to starting an AI task: a message and the latest 10 tasks.
 */
final readonly class AiTaskStarted
{
    /**
     * @param  list<AiTask>  $tasks
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $message,
        public array $tasks,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            message: $p->string('message'),
            tasks: $p->list('tasks', AiTask::fromArray(...)),
            raw: $data,
        );
    }

    /**
     * The response exactly as the API sent it, including fields this class does not type.
     *
     * @return array<array-key, mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }
}
