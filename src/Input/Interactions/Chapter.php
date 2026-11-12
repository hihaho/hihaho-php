<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Input\Fields;

final readonly class Chapter
{
    /**
     * @param  string  $title  at most 255 characters
     * @param  string|null  $ref  at most 255 characters; a jump action in the same request can target the chapter with `Action::jumpToChapterRef()`
     */
    public function __construct(
        public string $title,
        public float $startTime,
        public float $endTime,
        public ?string $titleShort = null,
        public ?int $sortOrder = null,
        public ?bool $isDisabled = null,
        public ?string $ref = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return Fields::build([
            'title' => $this->title,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'title_short' => $this->titleShort,
            'sort_order' => $this->sortOrder,
            'is_disabled' => $this->isDisabled,
            'ref' => $this->ref,
        ]);
    }
}
