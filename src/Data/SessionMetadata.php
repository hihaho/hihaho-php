<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Support\Payload;

/**
 * Technical details of one viewing session.
 */
final readonly class SessionMetadata
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public ?string $email,
        public ?bool $isEmbedded,
        public ?string $timezone,
        public ?DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $closedAt,
        public ?string $page,
        public ?string $referer,
        public ?string $userAgent,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            id: $p->int('id'),
            email: $p->nullableString('email'),
            isEmbedded: $p->nullableBool('is_embedded'),
            timezone: $p->nullableString('timezone'),
            startedAt: $p->date('started_at'),
            closedAt: $p->date('closed_at'),
            page: $p->nullableString('page'),
            referer: $p->nullableString('referer'),
            userAgent: $p->nullableString('user_agent'),
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
