<?php declare(strict_types=1);

namespace Hihaho\Sdk\Exceptions;

/**
 * 422. For a batch import the keys name the item, for example `2.action.link`.
 */
final class ValidationException extends HttpException
{
    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        $errors = $this->body()['errors'] ?? null;

        if (! is_array($errors)) {
            return [];
        }

        $typed = [];

        foreach ($errors as $field => $messages) {
            if (is_array($messages)) {
                $typed[(string) $field] = array_values(array_filter($messages, is_string(...)));
            }
        }

        return $typed;
    }
}
