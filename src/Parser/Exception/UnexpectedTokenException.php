<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Internal\ExpectationPrinter;

class UnexpectedTokenException extends ParserRuntimeException
{
    /**
     * @readonly
     */
    public array $expected = [];
    /**
     * @param int<0, max>|null $length
     */
    public function __construct(
        ReadableInterface $source,
        TokenInterface $token,
        string $message,
        array $expected = [],
        ?int $length = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        /**
         * The names of the tokens that could have been read instead of the one
         * the error occurred on.
         *
         * @var list<non-empty-string>
         */
        $this->expected = $expected;
        parent::__construct(
            $source,
            $token,
            $message,
            $length,
            $code,
            $previous,
        );
    }

    /**
     * @param list<non-empty-string> $expected
     */
    public static function becauseUnexpectedTokenProduced(
        ReadableInterface $source,
        TokenInterface $token,
        array $expected = [],
        ?\Throwable $previous = null
    ): self {
        return new self($source, $token, self::createMessage($token, $expected), $expected, null, 0, $previous);
    }

    /**
     * Reports the error the grammar describes by a message of its own.
     *
     * The message is reported as it is written: the place the reading has
     * broken at is told by the error itself, so there is nothing to say about
     * it that the grammar has not said.
     *
     * The code of the error is the rule the message is written on, counted
     * from one.
     *
     * @param non-empty-string $message
     * @param list<non-empty-string> $expected
     * @param int $rule the identifier of the rule the message is written on
     */
    public static function becauseGrammarDescribesTheError(
        ReadableInterface $source,
        TokenInterface $token,
        string $message,
        array $expected = [],
        int $rule = 0,
        ?\Throwable $previous = null
    ): self {
        return new self($source, $token, $message, $expected, null, $rule + 1, $previous);
    }

    /**
     * @param list<non-empty-string> $expected
     * @return non-empty-string
     */
    private static function createMessage(TokenInterface $token, array $expected): string
    {
        $message = \sprintf('Syntax error, unexpected %s', $token);

        if ($expected === []) {
            return $message;
        }

        return $message . \sprintf(', %s expected', ExpectationPrinter::printShort($expected));
    }
}
