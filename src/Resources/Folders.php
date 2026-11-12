<?php declare(strict_types=1);

namespace Hihaho\Sdk\Resources;

use Hihaho\Sdk\Data\Folder;
use Hihaho\Sdk\Enums\Locale;
use Hihaho\Sdk\Pagination\ListRequest;

/**
 * Folders (video containers) the token user can reach, including sub-folders.
 */
final readonly class Folders extends Resource
{
    /**
     * @return ListRequest<Folder>
     */
    public function list(): ListRequest
    {
        return $this->paged('v2/video-container', [], Folder::fromArray(...), fn (Folder $folder): int => $folder->id);
    }

    /**
     * `externalIdentifier` must be unique in the folder tree.
     *
     * @param  list<int>  $userIds  users of the folder tree to add, at most 100
     */
    public function createChild(
        int $parentId,
        string $name,
        string $externalIdentifier,
        ?Locale $defaultLocale = null,
        ?bool $requiresTwoFactorAuthentication = null,
        array $userIds = [],
    ): Folder {
        $body = array_filter([
            'name' => $name,
            'external_identifier' => $externalIdentifier,
            'default_locale' => $defaultLocale?->value,
            'requires_two_factor_authentication' => $requiresTwoFactorAuthentication,
            'user_id' => $userIds === [] ? null : array_values($userIds),
        ], fn (mixed $value): bool => $value !== null);

        return Folder::fromArray($this->data($this->connector->post("v2/video-container/{$parentId}/create", $body)));
    }
}
