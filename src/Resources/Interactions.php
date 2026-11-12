<?php declare(strict_types=1);

namespace Hihaho\Sdk\Resources;

use Hihaho\Sdk\Data\Interaction;
use Hihaho\Sdk\Http\Connector;
use Hihaho\Sdk\Input\Interactions\InteractionInput;
use Hihaho\Sdk\Pagination\ListRequest;

final readonly class Interactions extends Resource
{
    public function __construct(Connector $connector, private int $videoId)
    {
        parent::__construct($connector);
    }

    /**
     * @return ListRequest<Interaction>
     */
    public function list(): ListRequest
    {
        return $this->bare("v2/video/{$this->videoId}/interaction", [], Interaction::fromArray(...), fn (Interaction $interaction): int => $interaction->id);
    }

    /**
     * All or none. Returns the new ids in the order of `$items`. A 422 names the item by its
     * position, for example `2.action.link`.
     *
     * @param  list<InteractionInput>  $items
     * @return list<int>
     */
    public function createMany(array $items): array
    {
        return $this->ids($this->connector->post("v2/video/{$this->videoId}/interaction/batch", array_map(fn (InteractionInput $item): array => $item->toArray(), $items)));
    }

    /**
     * At most 100, all or none.
     *
     * @param  list<int>  $interactionIds
     */
    public function deleteMany(array $interactionIds): void
    {
        $this->connector->delete("v2/video/{$this->videoId}/interaction/batch", $this->idBatch($interactionIds, 100));
    }

    public function delete(int $interactionId): void
    {
        $this->connector->delete("v2/video/{$this->videoId}/interaction/{$interactionId}");
    }

    public function get(int $interactionId): Interaction
    {
        return Interaction::fromArray($this->getData("v2/video/{$this->videoId}/interaction/{$interactionId}"));
    }
}
