<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Enums\AiTaskStatus;
use Hihaho\Sdk\Enums\AiTaskType;
use Hihaho\Sdk\Enums\Locale;
use Hihaho\Sdk\Support\Payload;

final readonly class AiTask
{
    /**
     * @param  int|string|null  $taskTypeValue  raw value; `$taskType` is null when the SDK does not know it yet
     * @param  int|string|null  $targetLocaleValue  raw value; `$targetLocale` is null when the SDK does not know it yet
     * @param  int|string|null  $statusValue  raw value; `$status` is null when the SDK does not know it yet
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public int|string|null $taskTypeValue,
        public ?AiTaskType $taskType,
        public int|string|null $targetLocaleValue,
        public ?Locale $targetLocale,
        public int|string|null $statusValue,
        public ?AiTaskStatus $status,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $startedProgressingAt,
        public ?DateTimeImmutable $completedAt,
        public ?DateTimeImmutable $failedAt,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            id: $p->int('id'),
            taskTypeValue: $p->scalar('task_type'),
            taskType: $p->enum('task_type', AiTaskType::class),
            targetLocaleValue: $p->scalar('target_locale'),
            targetLocale: $p->enum('target_locale', Locale::class),
            statusValue: $p->scalar('status'),
            status: $p->enum('status', AiTaskStatus::class),
            createdAt: $p->date('created_at'),
            startedProgressingAt: $p->date('started_progressing_at'),
            completedAt: $p->date('completed_at'),
            failedAt: $p->date('failed_at'),
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
