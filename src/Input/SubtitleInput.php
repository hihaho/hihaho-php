<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input;

use Hihaho\Sdk\Enums\CornerPosition;
use Hihaho\Sdk\Enums\SubtitleType;
use InvalidArgumentException;

/**
 * One subtitle track for `POST v2/video/{video}/subtitle/batch`. The button settings only
 * apply to the default track; the API drops them for the others.
 */
final readonly class SubtitleInput
{
    public function __construct(
        public string $title,
        public string $language,
        public string $content,
        public ?SubtitleType $type = null,
        public ?int $delayMs = null,
        public ?bool $isRtl = null,
        public bool $isDefault = false,
        public ?CornerPosition $buttonPosition = null,
        public ?string $buttonTitle = null,
        public ?bool $hasEnabledInVideoButton = null,
        public ?bool $isEnabledOnStart = null,
        public ?bool $shouldShowButton = null,
    ) {}

    /**
     * Reads the track from a file. Without a type, a `.vtt` or `.srt` extension sets it.
     */
    public static function fromFile(
        string $path,
        string $title,
        string $language,
        ?SubtitleType $type = null,
        ?int $delayMs = null,
        ?bool $isRtl = null,
        bool $isDefault = false,
        ?CornerPosition $buttonPosition = null,
        ?string $buttonTitle = null,
        ?bool $hasEnabledInVideoButton = null,
        ?bool $isEnabledOnStart = null,
        ?bool $shouldShowButton = null,
    ): self {
        $content = is_file($path) ? file_get_contents($path) : false;

        if ($content === false) {
            throw new InvalidArgumentException("Cannot read {$path}.");
        }

        $type ??= SubtitleType::tryFrom(strtolower(pathinfo($path, PATHINFO_EXTENSION)))
            ?? throw new InvalidArgumentException("Cannot tell the subtitle type of {$path}. Pass a type, or use a .vtt or .srt file.");

        return new self($title, $language, $content, $type, $delayMs, $isRtl, $isDefault, $buttonPosition, $buttonTitle, $hasEnabledInVideoButton, $isEnabledOnStart, $shouldShowButton);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $fields = [
            'title' => $this->title,
            'language' => $this->language,
            'content' => $this->content,
            'type' => $this->type?->value,
            'delay' => $this->delayMs,
            'is_rtl' => $this->isRtl,
            'is_default' => $this->isDefault,
        ];

        if ($this->isDefault) {
            $fields += [
                'button_position' => $this->buttonPosition?->value,
                'button_title' => $this->buttonTitle,
                'has_enabled_in_video_button' => $this->hasEnabledInVideoButton,
                'is_enabled_on_start' => $this->isEnabledOnStart,
                'should_show_button' => $this->shouldShowButton,
            ];
        }

        return array_filter($fields, fn (mixed $value): bool => $value !== null);
    }
}
