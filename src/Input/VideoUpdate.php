<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input;

use Hihaho\Sdk\Enums\Embedding;
use Hihaho\Sdk\Enums\Locale;
use Hihaho\Sdk\Enums\SettableVideoStatus;
use InvalidArgumentException;

/**
 * Changes for `PUT v2/video/{video}`. A field you do not set is left out of the request, so the
 * server keeps its value. A field set to null clears it.
 */
final class VideoUpdate
{
    /** @var array<string, mixed> */
    private array $fields = [];

    public static function make(): self
    {
        return new self();
    }

    public function displayName(?string $displayName): self
    {
        return $this->with('display_name', $displayName);
    }

    public function authorNotes(?string $authorNotes): self
    {
        return $this->with('author_notes', $authorNotes);
    }

    public function status(SettableVideoStatus $status): self
    {
        return $this->with('status', $status->value);
    }

    public function embedding(Embedding $embedding): self
    {
        return $this->with('enable_embedding', $embedding->value);
    }

    /**
     * The language of the video.
     */
    public function locale(Locale $locale): self
    {
        return $this->with('locale', $locale->value);
    }

    /**
     * Null makes the video follow its folder's setting.
     */
    public function allowSeeking(?bool $allowSeeking): self
    {
        return $this->with('should_allow_seeking', $allowSeeking);
    }

    public function addAllowedDomain(string $domain): self
    {
        return $this->with('add_allowed_domain', $domain);
    }

    public function removeAllowedDomain(string $domain): self
    {
        return $this->with('remove_allowed_domain', $domain);
    }

    /**
     * Replaces all tags of the video. At most 10, each at most 128 characters.
     *
     * @param  list<string>  $tags
     */
    public function syncTags(array $tags): self
    {
        if (count($tags) > 10) {
            throw new InvalidArgumentException('A video can have at most 10 tags.');
        }

        return $this->with('tags', array_values($tags));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->fields;
    }

    private function with(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->fields[$key] = $value;

        return $clone;
    }
}
