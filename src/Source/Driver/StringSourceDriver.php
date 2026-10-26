<?php

declare(strict_types=1);

namespace Phplrt\Source\Driver;

use Phplrt\Source\StringSource;

/**
 * Creates a source out of a string containing the source code itself.
 *
 * @readonly
 */
final class StringSourceDriver implements SourceDriverInterface
{
    /**
     * @param mixed $source
     */
    public function tryCreate($source): ?StringSource
    {
        if (!\is_string($source)) {
            return null;
        }

        return new StringSource($source);
    }
}
