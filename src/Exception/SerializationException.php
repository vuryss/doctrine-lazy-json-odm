<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\Exception;

/**
 * Exception thrown when serialization or deserialization fails.
 */
class SerializationException extends \RuntimeException
{
    public static function serializationFailed(string $reason, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Serialization failed: %s', $reason),
            0,
            $previous
        );
    }

    public static function deserializationFailed(string $reason, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Deserialization failed: %s', $reason),
            0,
            $previous
        );
    }

    public static function invalidJsonData(string $data, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Invalid JSON data: %s', substr($data, 0, 100)),
            0,
            $previous
        );
    }

    public static function missingTypeInformation(): self
    {
        return new self('Missing type information in JSON data');
    }

    public static function unknownType(string $type): self
    {
        return new self(sprintf('Unknown type: %s', $type));
    }
}
