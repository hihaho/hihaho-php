<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Input\Fields;

final readonly class SummarySection
{
    /**
     * Pass both times or neither.
     */
    public function __construct(
        public string $body,
        public ?string $heading = null,
        public ?float $startTime = null,
        public ?float $endTime = null,
        public ?int $sortOrder = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return Fields::build([
            'body' => $this->body,
            'heading' => $this->heading,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'sort_order' => $this->sortOrder,
        ]);
    }
}
