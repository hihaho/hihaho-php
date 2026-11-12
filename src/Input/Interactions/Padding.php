<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\AlignItems;

final readonly class Padding
{
    /**
     * @param  float|null  $top  0 to 200
     * @param  float|null  $bottom  0 to 200
     */
    public function __construct(
        public ?float $top = null,
        public ?float $bottom = null,
        public ?AlignItems $alignItems = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['style' => ['padding_top' => $this->top, 'padding_bottom' => $this->bottom, 'align_items' => $this->alignItems?->value]];
    }
}
