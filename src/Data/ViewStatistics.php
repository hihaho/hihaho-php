<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Support\Payload;

/**
 * `period`, `startDate` and `endDate` are set when the request grouped by period.
 */
final readonly class ViewStatistics
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public ?string $period,
        public ?DateTimeImmutable $startDate,
        public ?DateTimeImmutable $endDate,
        public int $total,
        public int $started,
        public int $finished,
        public int $passed,
        public int $hasResult,
        public int $failed,
        public int $unfinished,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            period: $p->nullableString('period'),
            startDate: $p->date('start_date'),
            endDate: $p->date('end_date'),
            total: $p->int('total'),
            started: $p->int('started'),
            finished: $p->int('finished'),
            passed: $p->int('passed'),
            hasResult: $p->int('has_result'),
            failed: $p->int('failed'),
            unfinished: $p->int('unfinished'),
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
