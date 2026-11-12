<?php declare(strict_types=1);

namespace Hihaho\Sdk\Resources;

use Hihaho\Sdk\Data\Question;
use Hihaho\Sdk\Http\Connector;
use Hihaho\Sdk\Input\Questions\QuestionInput;
use Hihaho\Sdk\Pagination\ListRequest;

final readonly class Questions extends Resource
{
    public function __construct(Connector $connector, private int $videoId)
    {
        parent::__construct($connector);
    }

    /**
     * Answers and rubrics are not in the list; use `get()`.
     *
     * @return ListRequest<Question>
     */
    public function list(): ListRequest
    {
        return $this->bare("v2/video/{$this->videoId}/question", [], Question::fromArray(...), fn (Question $question): int => $question->id);
    }

    /**
     * All or none. Returns the new ids in the order of `$items`. A 422 names the item by its
     * position, for example `2.action.link`.
     *
     * @param  list<QuestionInput>  $items
     * @return list<int>
     */
    public function createMany(array $items): array
    {
        return $this->ids($this->connector->post("v2/video/{$this->videoId}/question/batch", array_map(fn (QuestionInput $item): array => $item->toArray(), $items)));
    }

    /**
     * At most 100, all or none.
     *
     * @param  list<int>  $questionIds
     */
    public function deleteMany(array $questionIds): void
    {
        $this->connector->delete("v2/video/{$this->videoId}/question/batch", $this->idBatch($questionIds, 100));
    }

    public function delete(int $questionId): void
    {
        $this->connector->delete("v2/video/{$this->videoId}/question/{$questionId}");
    }

    /**
     * This response is not wrapped in `data`.
     */
    public function get(int $questionId): Question
    {
        return Question::fromArray($this->getBare("v2/video/{$this->videoId}/question/{$questionId}"));
    }
}
