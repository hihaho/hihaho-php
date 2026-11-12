<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\TooltipPlacement;

final readonly class Tooltip
{
    /**
     * @param  string|null  $text  at most 255 characters
     */
    public function __construct(
        public ?string $text = null,
        public ?TooltipPlacement $placement = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['attributes' => ['tooltip_text' => $this->text, 'tooltip_placement' => $this->placement?->value]];
    }
}
