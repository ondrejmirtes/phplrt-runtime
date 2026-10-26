<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis;

/**
 * How far the analysis goes.
 *
 * Note: Upstream this is an enum. This fork supports PHP 7.4, so every case
 *       is a singleton returned by a static method of the same name.
 */
final class Mode
{
    /**
     * @var array<non-empty-string, self>
     */
    private static array $instances = [];

    /**
     * @var non-empty-string
     * @readonly
     */
    public string $name;

    /**
     * @param non-empty-string $name
     */
    private function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Only recognizes the source, no reducer is run.
     */
    public static function SyntaxCheck(): self
    {
        return self::$instances['SyntaxCheck'] ??= new self('SyntaxCheck');
    }

    /**
     * Recognizes the source and builds the result.
     */
    public static function Tolerant(): self
    {
        return self::$instances['Tolerant'] ??= new self('Tolerant');
    }
}
