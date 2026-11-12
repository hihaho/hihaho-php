<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use Hihaho\Sdk\Support\Payload;

final readonly class AggregatedInteractions
{
    /**
     * @param  list<InteractionStatisticDetail>  $details
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $count,
        public int $totalClicks,
        public array $details,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            count: $p->int('count'),
            totalClicks: $p->int('total_clicks'),
            details: $p->list('details', InteractionStatisticDetail::fromArray(...)),
            raw: $data,
        );
    }

    /**
     * The response exactly as the API sent it, including fields this class does not type.
     *
     * @return array<array-key, mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }
}
