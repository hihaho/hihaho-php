<?php declare(strict_types=1);

namespace Hihaho\Sdk\Resources;

use Hihaho\Sdk\Data\Video;
use Hihaho\Sdk\Data\VideoExport;
use Hihaho\Sdk\Data\ViewerToken;
use Hihaho\Sdk\Enums\Locale;
use Hihaho\Sdk\Http\Connector;
use Hihaho\Sdk\Input\VideoUpdate;
use InvalidArgumentException;

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
     * @param  array<string, string>  $variables  URL variables for the viewer's sessions, as name => value. At most 20;
     *                                          each value at most 255 characters.
     * @param  int|null  $lifetimeSeconds  null for a token that never expires
     */
    public function createViewerToken(string $viewerUid, array $variables = [], ?int $lifetimeSeconds = null): ViewerToken
    {
        $body = array_filter([
            'viewer_uid' => $viewerUid,
            'variables' => $variables === [] ? null : $variables,
            'lifetime' => $lifetimeSeconds,
        ], fn (mixed $value): bool => $value !== null);

        return ViewerToken::fromArray($this->connector->decode($this->connector->post("v2/video/{$this->videoId}/allowed_viewer_token", $body)));
    }

    /**
     * Copies the video into another locale and returns the copy at once; its questions, interactions
     * and subtitles are translated in the background. The locale must differ from the video's own.
     *
     * @param  list<int>|null  $questionIds  null copies every question; a list copies only those. At most 1000.
     * @param  list<int>|null  $interactionIds  null copies every interaction; a list copies only those. At most 1000.
     */
    public function createTranslatedCopy(Locale $locale, ?array $questionIds = null, ?array $interactionIds = null): Video
    {
        $body = array_filter([
            'locale' => $locale->value,
            'question_ids' => $this->optionalIds($questionIds),
            'interaction_ids' => $this->optionalIds($interactionIds),
        ], fn (mixed $value): bool => $value !== null);

        return Video::fromArray($this->data($this->connector->post("v2/video/{$this->videoId}/translated-copy", $body)));
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

    /**
     * @param  list<int>|null  $ids
     * @return list<int>|null
     */
    private function optionalIds(?array $ids): ?array
    {
        if ($ids !== null && count($ids) > 1000) {
            throw new InvalidArgumentException('Pass at most 1000 question or interaction ids.');
        }

        return $ids === null ? null : array_values($ids);
    }
}
