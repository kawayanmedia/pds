<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Exception;

use InvalidArgumentException;

/**
 * Raised when write input is not usable: a missing employee id, a blank
 * surname, an unknown education level and so on.
 */
final class ValidationException extends InvalidArgumentException
{
    /**
     * @var array<string, string>
     */
    private array $errors = [];

    public static function forField(string $field, string $message): self
    {
        $exception = new self($message);
        $exception->errors[$field] = $message;

        return $exception;
    }

    /**
     * @param array<string, string> $errors field => message
     */
    public static function withErrors(array $errors): self
    {
        $exception = new self(implode(' ', $errors));
        $exception->errors = $errors;

        return $exception;
    }

    /**
     * @return array<string, string> field => message
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
