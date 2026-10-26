<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * The token that has entered an embedded lexer, along with everything that
 * lexer has read.
 *
 * The token is written the way it is written in the source, so its value is
 * the fragment that entered the embedded lexer rather than the fragment read
 * by it: how far the reading has gone is told by {@see TokenEmbedding::$size}.
 *
 * @template-implements \IteratorAggregate<int, TokenInterface>
 * @template-implements \ArrayAccess<int, TokenInterface>
 *
 * @readonly
 */
final class TokenEmbedding extends Token implements
    \IteratorAggregate,
    \ArrayAccess,
    \Countable
{
    /**
     * @var list<TokenInterface>
     */
    public array $children = [];
    /**
     * @param non-empty-string|null $name
     * @param int<0, max> $offset
     * @param int<0, max> $size
     * @param list<TokenInterface> $children
     * @param list<string> $captures
     */
    public function __construct(
        int $id,
        ?string $name,
        ChannelInterface $channel,
        string $value,
        int $offset,
        int $size,
        array $children = [],
        array $captures = []
    ) {
        /**
         * The tokens the embedded lexer has read, in the order it has read
         * them.
         */
        $this->children = $children;
        // The token spans as far as the embedded lexer has read, rather than
        // as far as its own value does
        parent::__construct($id, $name, $channel, $value, $offset, $captures, $size);
    }

    /**
     * Creates the embedding of the given token.
     *
     * @param list<TokenInterface> $children
     * @param int<0, max> $size
     */
    public static function createFromToken(Token $token, array $children, int $size): self
    {
        return new self(
            $token->id,
            $token->name,
            $token->channel,
            $token->value,
            $token->offset,
            $size,
            $children,
            $token->captures,
        );
    }

    /**
     * @return \Traversable<int, TokenInterface>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children);
    }

    /**
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->children[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetGet($offset): TokenInterface
    {
        if (!isset($this->children[$offset])) {
            throw new \OutOfRangeException(
                'The embedded lexer has read no token at the given position',
            );
        }
        return $this->children[$offset];
    }

    /**
     * @return never
     * @param mixed $offset
     * @param mixed $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('A token is immutable');
    }

    /**
     * @return never
     * @param mixed $offset
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new \LogicException('A token is immutable');
    }

    public function count(): int
    {
        return \count($this->children);
    }
}
