<?php declare(strict_types=1);

namespace Hihaho\Sdk\Resources;

use Hihaho\Sdk\Data\AggregatedStatistics;
use Hihaho\Sdk\Data\InteractionStatistic;
use Hihaho\Sdk\Data\QuestionStatistic;
use Hihaho\Sdk\Data\SessionMetadata;
use Hihaho\Sdk\Data\ViewStatistics;
use Hihaho\Sdk\Enums\PerPage;
use Hihaho\Sdk\Http\Connector;
use Hihaho\Sdk\Input\StatisticsFilter;
use Hihaho\Sdk\Pagination\ListRequest;

final readonly class Statistics extends Resource
{
    public function __construct(Connector $connector, private int $videoId)
    {
        parent::__construct($connector);
    }

    /**
     * Throws a precondition-failed exception when the folder has aggregated statistics switched
     * off.
     */
    public function aggregated(): AggregatedStatistics
    {
        return AggregatedStatistics::fromArray($this->getData("v2/video/{$this->videoId}/aggregated-statistics"));
    }

    /**
     * One row without `groupBy`, one row per period with it.
     *
     * @return list<ViewStatistics>
     */
    public function views(StatisticsFilter $filter = new StatisticsFilter()): array
    {
        $data = $this->getData("v2/video/{$this->videoId}/stats/views", $filter->toQuery());

        if (! array_is_list($data)) {
            return [ViewStatistics::fromArray($data)];
        }

        return array_values(array_map(ViewStatistics::fromArray(...), array_filter($data, is_array(...))));
    }

    /**
     * View count per user agent string. Views without a user agent are counted under `unknown`.
     *
     * @return array<string, int>
     */
    public function viewsByUserAgent(StatisticsFilter $filter = new StatisticsFilter()): array
    {
        $counts = [];

        foreach ($this->getData("v2/video/{$this->videoId}/stats/views-by-user-agent", $filter->toQuery()) as $userAgent => $count) {
            $counts[(string) $userAgent] = is_int($count) ? $count : 0;
        }

        return $counts;
    }

    /**
     * @return ListRequest<InteractionStatistic>
     */
    public function interactions(StatisticsFilter $filter = new StatisticsFilter()): ListRequest
    {
        return $this->bare("v2/video/{$this->videoId}/stats/interactions", $filter->toQuery(), InteractionStatistic::fromArray(...), fn (InteractionStatistic $statistic): int => $statistic->id);
    }

    /**
     * @return ListRequest<QuestionStatistic>
     */
    public function questions(StatisticsFilter $filter = new StatisticsFilter()): ListRequest
    {
        return $this->bare("v2/video/{$this->videoId}/stats/questions", $filter->toQuery(), QuestionStatistic::fromArray(...), fn (QuestionStatistic $statistic): int => $statistic->id);
    }

    /**
     * @return ListRequest<SessionMetadata>
     */
    public function metadata(StatisticsFilter $filter = new StatisticsFilter(), PerPage $perPage = PerPage::Hundred): ListRequest
    {
        return $this->paged(
            "v2/video/{$this->videoId}/stats/metadata",
            [...$filter->toQuery(), 'per_page' => $perPage->value],
            SessionMetadata::fromArray(...),
            fn (SessionMetadata $metadata): int => $metadata->id,
        );
    }

    public function sessionMetadata(int $sessionId): SessionMetadata
    {
        return SessionMetadata::fromArray($this->getData("v2/video/{$this->videoId}/stats/metadata/{$sessionId}"));
    }
}
