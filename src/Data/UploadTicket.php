<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use Hihaho\Sdk\Enums\UploadMethod;
use Hihaho\Sdk\Support\Payload;

/**
 * Where and how to send the file of a registered upload. The keys are camelCase in this response.
 */
final readonly class UploadTicket
{
    /**
     * @param  int|string|null  $uploadMethodValue  raw value; `$uploadMethod` is null when the SDK does not know it yet
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public string $url,
        public string $contentType,
        public int|string|null $uploadMethodValue,
        public ?UploadMethod $uploadMethod,
        public ?UploadVideoRef $video,
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
            url: $p->string('url'),
            contentType: $p->string('contentType'),
            uploadMethodValue: $p->scalar('uploadMethod'),
            uploadMethod: $p->enum('uploadMethod', UploadMethod::class),
            video: $p->nullableArray('video') === null ? null : UploadVideoRef::fromArray($p->array('video')),
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
