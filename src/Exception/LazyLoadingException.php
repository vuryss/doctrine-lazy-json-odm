<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\Exception;

/**
 * Exception thrown when lazy loading fails.
 */
class LazyLoadingException extends \RuntimeException
{
    public static function initializationFailed(string $reason, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Lazy object initialization failed: %s', $reason),
            0,
            $previous
        );
    }

    public static function invalidLazyObject(): self
    {
        return new self('Invalid lazy object state');
    }

    public static function serializerNotAvailable(): self
    {
        return new self('Serializer not available for lazy object initialization');
    }

    public static function collectionInitializationFailed(string $reason, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Lazy collection initialization failed: %s', $reason),
            0,
            $previous
        );
    }
}
