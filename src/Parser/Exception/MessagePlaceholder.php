<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

/**
 * A part of an error message standing for what the reading has broken on.
 *
 * A placeholder is written in braces, the way it is written for a logger, and
 * a brace of the message itself is written twice:
 *
 * ```
 * unexpected {token} on line {line}, a {{name}} is expected
 * ```
 */
/**
 * Note: Upstream this is a string-backed enum. This fork supports PHP 7.4,
 *       so the cases are string constants of the same names.
 */
final class MessagePlaceholder
{
    /**
     * Recognizes a placeholder of a message along with the pair of braces
     * standing for a brace of the message itself.
     *
     * The name of a placeholder is captured by the first subgroup, which is
     * absent from a pair of braces.
     *
     * @var non-empty-string
     */
    public const PATTERN = '/\{\{|\}\}|\{([a-zA-Z][a-zA-Z0-9_]*+)\}/';

    /**
     * The token the reading has broken on, along with everything known about
     * it.
     */
    public const Token = 'token';

    /**
     * The name of the token the reading has broken on, or its identifier in
     * case of an anonymous one.
     */
    public const Name = 'name';

    /**
     * The text the token the reading has broken on is read from.
     */
    public const Value = 'value';

    /**
     * The offset in bytes from the beginning of the source the reading has
     * broken at.
     */
    public const Offset = 'offset';

    /**
     * The number of the source line the reading has broken on.
     */
    public const Line = 'line';

    /**
     * The number of the column within its own line the reading has broken at.
     */
    public const Column = 'column';

    /**
     * The first few names of the tokens that could have been read instead,
     * along with the number of the ones left out.
     */
    public const Expected = 'expected';

    /**
     * The names of every token that could have been read instead.
     */
    public const ExpectedList = 'expected_list';

    /**
     * Returns every placeholder the way it is written in a message.
     *
     * @return non-empty-list<non-empty-string>
     */
    public static function names(): array
    {
        return [
            self::Token,
            self::Name,
            self::Value,
            self::Offset,
            self::Line,
            self::Column,
            self::Expected,
            self::ExpectedList,
        ];
    }

    /**
     * @return non-empty-string|null
     */
    public static function tryFrom(string $name): ?string
    {
        return \in_array($name, self::names(), true) ? $name : null;
    }
}
