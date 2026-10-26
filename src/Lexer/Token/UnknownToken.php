<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\Channel;

final class UnknownToken extends Token
{
    /**
     * An identifier of the pseudo-token describing a source fragment
     * that could not be read.
     */
    public const TOKEN_ID = -2;

    /**
     * @param int<0, max> $offset
     */
    public function __construct(string $value, int $offset)
    {
        parent::__construct(
            self::TOKEN_ID,
            null,
            Channel::Unknown(),
            $value,
            $offset,
        );
    }
}
