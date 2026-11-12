<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Support\Payload;

/**
 * A personal viewer token for a video that only selected viewers may watch.
 */
final readonly class ViewerToken
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $token,
        public ?DateTimeImmutable $expiresAt,
        public string $playerUrl,
        public string $embedUrl,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            token: $p->string('token'),
            expiresAt: $p->date('expires_at'),
            playerUrl: $p->string('player_url'),
            embedUrl: $p->string('embed_url'),
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
