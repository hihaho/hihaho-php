<?php declare(strict_types=1);

namespace Hihaho\Sdk\Resources;

use Hihaho\Sdk\Data\Session;
use Hihaho\Sdk\Data\SessionDetail;
use Hihaho\Sdk\Enums\PerPage;
use Hihaho\Sdk\Http\Connector;
use Hihaho\Sdk\Input\StatisticsFilter;
use Hihaho\Sdk\Pagination\ListRequest;

final readonly class Sessions extends Resource
{
    public function __construct(Connector $connector, private int $videoId)
    {
        parent::__construct($connector);
    }

    /**
     * A deep walk over a busy video is slow on the server; narrow it with a date window.
     *
     * @return ListRequest<Session>
     */
    public function list(StatisticsFilter $filter = new StatisticsFilter(), PerPage $perPage = PerPage::Hundred): ListRequest
    {
        return $this->paged(
            "v2/video/{$this->videoId}/session",
            [...$filter->toQuery(), 'per_page' => $perPage->value],
            Session::fromArray(...),
            fn (Session $session): int => $session->id,
        );
    }

    /**
     * With every answer.
     */
    public function get(int $sessionId): SessionDetail
    {
        return SessionDetail::fromArray($this->getData("v2/video/{$this->videoId}/session/{$sessionId}"));
    }
}
