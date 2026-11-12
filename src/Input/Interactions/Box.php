<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

/**
 * Position and size of an interaction on the video, plus its border. Position and size are CSS
 * strings, for example `10%`.
 */
final readonly class Box
{
    /**
     * @param  int|null  $borderRadius  0 to 360
     */
    public function __construct(
        public ?string $top = null,
        public ?string $left = null,
        public ?string $width = null,
        public ?string $height = null,
        public ?Border $border = null,
        public ?int $borderRadius = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['style' => [
            'top' => $this->top,
            'left' => $this->left,
            'width' => $this->width,
            'height' => $this->height,
            'border' => $this->border?->toArray(),
            'border_radius' => $this->borderRadius,
        ]];
    }
}
