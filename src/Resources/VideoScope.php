<?php declare(strict_types=1);

namespace Hihaho\Sdk\Resources;

use Hihaho\Sdk\Data\Video;
use Hihaho\Sdk\Data\VideoExport;
use Hihaho\Sdk\Data\ViewerToken;
use Hihaho\Sdk\Http\Connector;
use Hihaho\Sdk\Input\VideoUpdate;

/**
 * One video and everything that belongs to it. Get it with `$hihaho->video($videoId)`; it sends no
 * request until you call a method.
 */
final readonly class VideoScope extends Resource
{
    public function __construct(Connector $connector, private int $videoId)
    {
        parent::__construct($connector);
    }

    public function get(): Video
    {
        return Video::fromArray($this->getData("v2/video/{$this->videoId}"));
    }

    public function update(VideoUpdate $update): Video
    {
        return Video::fromArray($this->data($this->connector->put("v2/video/{$this->videoId}", $update->toArray())));
    }

    /**
     * Interactions, questions and subtitles in import format.
     */
    public function export(): VideoExport
    {
        return VideoExport::fromArray($this->getData("v2/video/{$this->videoId}/export"));
    }

    /**
     * @param  list<string>  $variables  URL variables for the viewer's sessions, at most 20
     * @param  int|null  $lifetimeSeconds  null for a token that never expires
     */
    public function createViewerToken(string $viewerUid, array $variables = [], ?int $lifetimeSeconds = null): ViewerToken
    {
        $body = array_filter([
            'viewer_uid' => $viewerUid,
            'variables' => $variables === [] ? null : array_values($variables),
            'lifetime' => $lifetimeSeconds,
        ], fn (mixed $value): bool => $value !== null);

        return ViewerToken::fromArray($this->connector->decode($this->connector->post("v2/video/{$this->videoId}/allowed_viewer_token", $body)));
    }

    public function interactions(): Interactions
    {
        return new Interactions($this->connector, $this->videoId);
    }

    public function questions(): Questions
    {
        return new Questions($this->connector, $this->videoId);
    }

    public function subtitles(): Subtitles
    {
        return new Subtitles($this->connector, $this->videoId);
    }

    public function thumbnails(): Thumbnails
    {
        return new Thumbnails($this->connector, $this->videoId);
    }

    public function aiTasks(): AiTasks
    {
        return new AiTasks($this->connector, $this->videoId);
    }

    public function sessions(): Sessions
    {
        return new Sessions($this->connector, $this->videoId);
    }

    public function statistics(): Statistics
    {
        return new Statistics($this->connector, $this->videoId);
    }
}
