<?php declare(strict_types=1);

namespace Hihaho\Sdk\Resources;

use Hihaho\Sdk\Data\PlatformThumbnail;
use Hihaho\Sdk\Data\Video;
use Hihaho\Sdk\Http\Connector;
use Hihaho\Sdk\Pagination\ListRequest;

/**
 * Thumbnails of one video on its video platform. Only for a video uploaded through hihaho whose first
 * upload has finished processing; for any other video, every call throws `AuthorizationException` (403).
 */
final readonly class Thumbnails extends Resource
{
    public function __construct(Connector $connector, private int $videoId)
    {
        parent::__construct($connector);
    }

    /**
     * @return ListRequest<PlatformThumbnail>
     */
    public function list(): ListRequest
    {
        return $this->bare("v2/video/{$this->videoId}/thumbnail", [], PlatformThumbnail::fromArray(...), fn (PlatformThumbnail $thumbnail): string => $thumbnail->id);
    }

    /**
     * An image of at most 5 MB.
     */
    public function upload(string $imagePath): Video
    {
        return Video::fromArray($this->data($this->connector->postFile("v2/video/{$this->videoId}/thumbnail", 'thumbnail', $imagePath)));
    }

    /**
     * The platform saves it in the background.
     */
    public function activate(string $thumbnailId): void
    {
        $this->connector->put("v2/video/{$this->videoId}/thumbnail/" . rawurlencode($thumbnailId) . '/activate');
    }

    /**
     * The active poster cannot be deleted.
     */
    public function delete(string $thumbnailId): void
    {
        $this->connector->delete("v2/video/{$this->videoId}/thumbnail/" . rawurlencode($thumbnailId));
    }
}
