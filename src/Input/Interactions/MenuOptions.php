<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\DisplayLocation;
use Hihaho\Sdk\Enums\MenuButtonPosition;

final readonly class MenuOptions
{
    /**
     * @param  string|null  $buttonName  at most 150 characters
     */
    public function __construct(
        public ?DisplayLocation $buttonLocation = null,
        public ?string $buttonName = null,
        public ?MenuButtonPosition $buttonPosition = null,
        public ?bool $canBeClosed = null,
        public ?bool $showButton = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['attributes' => ['options' => [
            'menu_button_location' => $this->buttonLocation?->value,
            'menu_button_name' => $this->buttonName,
            'menu_button_position' => $this->buttonPosition?->value,
            'should_give_possibility_to_close' => $this->canBeClosed,
            'should_show_menu_button' => $this->showButton,
        ]]];
    }
}
