<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\PopupButtonLabel;

/**
 * Shows the interaction as a popup behind a button.
 */
final readonly class Popup
{
    /**
     * @param  string|null  $labelText  at most 50 characters
     * @param  string|null  $buttonLeft  a percentage, for example `40%`
     * @param  string|null  $buttonTop  a percentage, for example `40%`
     */
    public function __construct(
        public ?PopupButtonLabel $labelType = null,
        public ?string $labelText = null,
        public ?string $buttonLeft = null,
        public ?string $buttonTop = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'should_show_as_popup' => true,
            'style' => ['popup_button_left' => $this->buttonLeft, 'popup_button_top' => $this->buttonTop],
            'attributes' => ['popup_button_label_type' => $this->labelType?->value, 'popup_button_label_text' => $this->labelText],
        ];
    }
}
