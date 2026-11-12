<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\InteractionActionType;
use Hihaho\Sdk\Input\Fields;

/**
 * What an interaction does when a viewer clicks it.
 *
 * A jump can target an existing item by id, or an item in the same request by its `ref`. The API
 * answers 422 on `target_ref` when no item in the request has that ref, or when the ref names the
 * wrong kind of item. A ref cannot target a question: questions are imported through another request.
 */
final readonly class Action
{
    /**
     * @param  array<string, mixed>  $fields
     */
    private function __construct(
        public InteractionActionType $type,
        private array $fields = [],
    ) {}

    public static function none(): self
    {
        return new self(InteractionActionType::NoAction);
    }

    public static function link(string $url, ?bool $openInSameWindow = null): self
    {
        return new self(InteractionActionType::Link, ['link' => $url, 'should_open_in_same_window' => $openInSameWindow]);
    }

    /**
     * Opens another published hihaho video, by its UUID.
     */
    public static function linkVideo(string $videoUuid): self
    {
        return new self(InteractionActionType::LinkVideo, ['link' => $videoUuid]);
    }

    public static function jump(float $toSeconds): self
    {
        return new self(InteractionActionType::Jump, ['time' => $toSeconds]);
    }

    public static function jumpToStart(): self
    {
        return new self(InteractionActionType::JumpToStart);
    }

    public static function jumpToEnd(): self
    {
        return new self(InteractionActionType::JumpToEnd);
    }

    public static function jumpToInteraction(int $interactionId): self
    {
        return new self(InteractionActionType::JumpToInteraction, ['interaction_id' => $interactionId]);
    }

    public static function jumpToQuestion(int $questionId): self
    {
        return new self(InteractionActionType::JumpToInteraction, ['question_id' => $questionId]);
    }

    public static function jumpToChapter(int $chapterId): self
    {
        return new self(InteractionActionType::JumpToChapter, ['chapter_id' => $chapterId]);
    }

    /**
     * Jumps to the interaction with this `ref` in the same request.
     */
    public static function jumpToInteractionRef(string $ref): self
    {
        return new self(InteractionActionType::JumpToInteraction, ['target_ref' => $ref]);
    }

    /**
     * Jumps to the chapter item with this `ref` in the same request.
     */
    public static function jumpToChapterRef(string $ref): self
    {
        return new self(InteractionActionType::JumpToChapter, ['target_ref' => $ref]);
    }

    public static function showScoreScreen(): self
    {
        return new self(InteractionActionType::ShowScoreScreen);
    }

    /**
     * Jumps to `toSeconds` when the viewer clicks, and shows the button with these colours.
     */
    public static function delayedJump(float $toSeconds, ?float $jumpAtSeconds = null, ?string $backgroundColor = null, ?string $textColor = null): self
    {
        $hasStyle = $backgroundColor !== null || $textColor !== null;

        return new self(InteractionActionType::DelayedJump, [
            'time' => $toSeconds,
            'jump_at_position' => $jumpAtSeconds,
            // The API needs both keys whenever activation_style is sent.
            'activation_style' => $hasStyle ? ['background_color' => $backgroundColor ?? '', 'text_color' => $textColor ?? ''] : null,
        ]);
    }

    /**
     * Switches on one subtitle track of the same video, by its encoded subtitle id.
     */
    public static function enableSubtitle(int $subtitleRouteKey): self
    {
        return new self(InteractionActionType::EnableSubtitle, ['subtitle_route_key' => $subtitleRouteKey]);
    }

    public static function toggleSound(): self
    {
        return new self(InteractionActionType::ToggleSound);
    }

    /**
     * @param  array<string, mixed>  $extra  type-specific action fields (frame, pause, video link)
     * @return array<string, mixed>
     */
    public function toArray(array $extra = []): array
    {
        return Fields::build(['type' => $this->type->value, ...$this->fields, ...$extra]);
    }
}
