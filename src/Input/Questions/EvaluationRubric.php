<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

/**
 * One criterion the AI checks an open answer against.
 */
final readonly class EvaluationRubric
{
    public function __construct(
        public ?string $name = null,
        public ?string $text = null,
        public bool $isActive = true,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['name' => $this->name, 'text' => $this->text, 'is_active' => $this->isActive];
    }
}
