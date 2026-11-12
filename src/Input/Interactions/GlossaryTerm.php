<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Input\Fields;

final readonly class GlossaryTerm
{
    /**
     * @param  list<string>  $surfaceForms  other spellings of the term, at most 32
     * @param  list<array{start: float, end: float}>  $references  moments in the video, at most 64
     */
    public function __construct(
        public string $term,
        public string $definition,
        public array $surfaceForms = [],
        public array $references = [],
        public ?int $sortOrder = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return Fields::build([
            'term' => $this->term,
            'definition' => $this->definition,
            'surface_forms' => $this->surfaceForms === [] ? null : $this->surfaceForms,
            'references' => $this->references === [] ? null : $this->references,
            'sort_order' => $this->sortOrder,
        ]);
    }
}
