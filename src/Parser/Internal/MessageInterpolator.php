<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Position\PositionFactoryInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Exception\MessagePlaceholder;
use Phplrt\Position\PositionFactory;

/**
 * Fills the placeholders of an error message in with what the reading has
 * broken on.
 *
 * A placeholder is written in braces, the way it is written for a logger, and
 * a brace of the message itself is written twice:
 *
 * ```
 * unexpected {token} on line {line}, a {{name}} is expected
 * ```
 *
 * A placeholder no value is known for is left exactly as it is written.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 *
 * @readonly
 */
final class MessageInterpolator
{
    /**
     * @readonly
     */
    private PositionFactoryInterface $factory;
    /**
     * The place of the source a token starts at, indexed by that token.
     *
     * A line and a column are counted by reading the source from its
     * beginning, so the answer is remembered for as long as the token it has
     * been asked about is alive.
     *
     * Note: Upstream this is a WeakMap, which does not exist on PHP 7.4.
     *
     * @var \SplObjectStorage<TokenInterface, PositionInterface>
     * @readonly
     */
    private \SplObjectStorage $positions;

    public function __construct(
        ?PositionFactoryInterface $factory = null
    ) {
        $factory ??= new PositionFactory();
        /**
         * Tells the line and the column of the place the reading has stopped
         * at, in case the message asks about either of them.
         */
        $this->factory = $factory;
        $this->positions = new \SplObjectStorage();
    }

    /**
     * Returns the given message with its placeholders filled in.
     *
     * @param non-empty-string $message
     * @param list<non-empty-string> $expected the names of the tokens that
     *        could have been read instead of the one the error occurred on
     * @return non-empty-string
     * @throws SourceExceptionInterface in case of the message asks about a
     *         line or a column and the source cannot be read
     */
    public function interpolate(
        string $message,
        ReadableInterface $source,
        TokenInterface $token,
        array $expected = []
    ): string {
        $result = \preg_replace_callback(
            MessagePlaceholder::PATTERN,
            fn(array $matches): string
                => $this->createReplacement($matches, $source, $token, $expected),
            $message,
        );

        // Note: An error is reported by what it says, so a message emptied by
        //       what it asks about is reported the way it is written instead
        if ($result === null || $result === '') {
            return $message;
        }

        return $result;
    }

    /**
     * @param array{0: non-empty-string, 1?: non-empty-string} $matches the whole
     *        placeholder, along with its name in case it has been captured
     * @param list<non-empty-string> $expected
     * @throws SourceExceptionInterface
     */
    private function createReplacement(
        array $matches,
        ReadableInterface $source,
        TokenInterface $token,
        array $expected
    ): string {
        $name = $matches[1] ?? null;

        // A pair of braces stands for a brace of the message itself
        if ($name === null) {
            return $matches[0][0];
        }

        $placeholder = MessagePlaceholder::tryFrom($name);

        // A placeholder the reading knows no value for belongs to the message
        // rather than to the parser
        if ($placeholder === null) {
            return $matches[0];
        }

        switch ($placeholder) {
            case MessagePlaceholder::Token:
                return self::createToken($token);
            case MessagePlaceholder::Name:
                return self::createName($token);
            case MessagePlaceholder::Value:
                return self::createValue($token);
            case MessagePlaceholder::Offset:
                return self::createOffset($token);
            case MessagePlaceholder::Line:
                return $this->createLine($source, $token);
            case MessagePlaceholder::Column:
                return $this->createColumn($source, $token);
            case MessagePlaceholder::Expected:
                return self::createExpected($expected);
            case MessagePlaceholder::ExpectedList:
                return self::createExpectedList($expected);
        }
    }

    private static function createToken(TokenInterface $token): string
    {
        return (string) $token;
    }

    private static function createName(TokenInterface $token): string
    {
        return $token->name ?? (string) $token->id;
    }

    private static function createValue(TokenInterface $token): string
    {
        return $token->value;
    }

    private static function createOffset(TokenInterface $token): string
    {
        return (string) $token->offset;
    }

    /**
     * @throws SourceExceptionInterface
     */
    private function createLine(ReadableInterface $source, TokenInterface $token): string
    {
        return (string) $this->findPosition($source, $token)->line;
    }

    /**
     * @throws SourceExceptionInterface
     */
    private function createColumn(ReadableInterface $source, TokenInterface $token): string
    {
        return (string) $this->findPosition($source, $token)->column;
    }

    /**
     * @param list<non-empty-string> $expected
     */
    private static function createExpected(array $expected): string
    {
        return ExpectationPrinter::printShort($expected);
    }

    /**
     * @param list<non-empty-string> $expected
     */
    private static function createExpectedList(array $expected): string
    {
        return ExpectationPrinter::printAll($expected);
    }

    /**
     * Returns the place of the source the given token starts at.
     *
     * @throws SourceExceptionInterface
     */
    private function findPosition(ReadableInterface $source, TokenInterface $token): PositionInterface
    {
        return $this->positions[$token] ??= $this->factory
            ->createFromOffset($source, $token->offset);
    }
}
