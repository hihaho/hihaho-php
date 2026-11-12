<?php declare(strict_types=1);

namespace Hihaho\Sdk\Resources;

use Hihaho\Sdk\Data\Subtitle;
use Hihaho\Sdk\Http\Connector;
use Hihaho\Sdk\Input\SubtitleInput;
use Hihaho\Sdk\Pagination\ListRequest;

final readonly class Subtitles extends Resource
{
    public function __construct(Connector $connector, private int $videoId)
    {
        parent::__construct($connector);
    }

    /**
     * @return ListRequest<Subtitle>
     */
    public function list(): ListRequest
    {
        return $this->bare("v2/video/{$this->videoId}/subtitles", [], Subtitle::fromArray(...), fn (Subtitle $subtitle): int => $subtitle->id);
    }

    /**
     * Not a transaction: when one track fails, the tracks before it stay saved.
     *
     * @param  list<SubtitleInput>  $subtitles
     */
    public function createMany(array $subtitles): void
    {
        $this->connector->post("v2/video/{$this->videoId}/subtitle/batch", array_map(fn (SubtitleInput $subtitle): array => $subtitle->toArray(), $subtitles));
    }
}
