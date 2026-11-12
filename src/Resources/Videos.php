<?php declare(strict_types=1);

namespace Hihaho\Sdk\Resources;

use Hihaho\Sdk\Data\SwappedVideos;
use Hihaho\Sdk\Data\Video;
use Hihaho\Sdk\Data\VideoSummary;
use Hihaho\Sdk\Enums\VideoPlatform;
use Hihaho\Sdk\Enums\VideoStatus;
use Hihaho\Sdk\Pagination\ListRequest;

final readonly class Videos extends Resource
{
    /**
     * With a folder, videos in its sub-folders are included. The API does not sort this list, so a
     * walk over many pages can miss a video.
     *
     * @return ListRequest<VideoSummary>
     */
    public function list(?string $search = null, ?VideoStatus $status = null, ?int $folderId = null): ListRequest
    {
        $query = array_filter([
            'q' => $search,
            'status' => $status?->value,
            'video_container_id' => $folderId,
        ], fn (mixed $value): bool => $value !== null);

        return $this->paged('v2/video', $query, VideoSummary::fromArray(...), fn (VideoSummary $video): int => $video->id);
    }

    /**
     * A YouTube or Vimeo URL is reduced to its id.
     */
    public function createFromPlatform(int $folderId, string $platformReference, VideoPlatform $platform): Video
    {
        $body = ['platform_reference' => $platformReference, 'platform' => $platform->value];

        return Video::fromArray($this->data($this->connector->post("v2/video-container/{$folderId}/video-from-platform", $body)));
    }

    /**
     * At most 200 videos, all or none. Enterprise accounts only. Ids the API cannot find are
     * skipped.
     *
     * @param  list<int>  $videoIds
     */
    public function deleteMany(array $videoIds): void
    {
        $this->connector->delete('v2/video/batch', $this->idBatch($videoIds, 200));
    }

    /**
     * At most 100 videos, all or none.
     *
     * @param  list<int>  $videoIds
     */
    public function moveMany(array $videoIds, int $folderId): void
    {
        $this->connector->put("v2/video/batch/move/{$folderId}", $this->idBatch($videoIds, 100));
    }

    /**
     * The two videos swap their UUIDs, so the player and embed URLs point at the other video. Takes
     * UUIDs, not ids.
     */
    public function swap(string $fromUuid, string $toUuid): SwappedVideos
    {
        return SwappedVideos::fromArray($this->connector->decode($this->connector->post("v2/video/swap/from/{$fromUuid}/to/{$toUuid}")));
    }
}
