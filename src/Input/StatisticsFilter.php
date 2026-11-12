<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input;

use DateTimeInterface;
use Hihaho\Sdk\Enums\GroupByPeriod;
use InvalidArgumentException;

/**
 * Filters for the statistics and session lists. The API uses only the date of `from` and
 * `until`: it counts from the start of `from`'s day to the end of `until`'s day.
 */
final readonly class StatisticsFilter
{
    /**
     * @param  list<string>  $variables  URL variables a session must have, at most 20 (25 for the session list)
     */
    public function __construct(
        public ?DateTimeInterface $from = null,
        public ?DateTimeInterface $until = null,
        public ?GroupByPeriod $groupBy = null,
        public array $variables = [],
    ) {
        if ((! $from instanceof DateTimeInterface) !== (! $until instanceof DateTimeInterface)) {
            throw new InvalidArgumentException('Pass both from and until, or neither.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toQuery(): array
    {
        return array_filter([
            'start_date' => $this->from?->format('Y-m-d H:i:s'),
            'end_date' => $this->until?->format('Y-m-d H:i:s'),
            'group_by' => $this->groupBy?->value,
            'variables' => $this->variables === [] ? null : $this->variables,
        ], fn (mixed $value): bool => $value !== null);
    }
}
