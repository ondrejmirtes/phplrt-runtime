<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * The channels built into the lexer.
 *
 * Note: Upstream this is an enum. This fork supports PHP 7.4, so every case
 *       is a singleton returned by a static method of the same name, and the
 *       instances may be compared with "===" the same way enum cases are.
 */
final class Channel implements ChannelInterface
{
    private const CASES = ['Default', 'Hidden', 'Unknown', 'EndOfInput'];

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

    public static function Default(): self
    {
        return self::$instances['Default'] ??= new self('Default');
    }

    public static function Hidden(): self
    {
        return self::$instances['Hidden'] ??= new self('Hidden');
    }

    public static function Unknown(): self
    {
        return self::$instances['Unknown'] ??= new self('Unknown');
    }

    public static function EndOfInput(): self
    {
        return self::$instances['EndOfInput'] ??= new self('EndOfInput');
    }

    /**
     * @return list<self>
     */
    public static function cases(): array
    {
        $result = [];

        foreach (self::CASES as $case) {
            $result[] = self::$case();
        }

        return $result;
    }

    /**
     * @return array<non-empty-string, self>
     */
    public static function names(): array
    {
        $result = [];

        foreach (self::cases() as $case) {
            $result[$case->name] = $case;
        }

        return $result;
    }
}
