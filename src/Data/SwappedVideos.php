<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use Hihaho\Sdk\Support\Payload;

/**
 * The two videos after their UUIDs were swapped.
 */
final readonly class SwappedVideos
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public Video $from,
        public Video $to,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $videos = new Payload($data)->object('videos');

        return new self(
            from: Video::fromArray($videos->array('updated_from')),
            to: Video::fromArray($videos->array('updated_to')),
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
