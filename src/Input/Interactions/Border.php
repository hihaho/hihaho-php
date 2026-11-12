<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\BorderStyle;

final readonly class Border
{
    /**
     * @param  int|null  $width  0 to 200 pixels
     * @param  string|null  $color  `#rrggbb`, `rgb(...)` or `rgba(...)`
     */
    public function __construct(
        public ?int $width = null,
        public ?BorderStyle $style = null,
        public ?string $color = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['border_width' => $this->width, 'border_style' => $this->style?->value, 'border_color' => $this->color];
    }
}
