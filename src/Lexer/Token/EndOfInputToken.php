<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\Channel;

class EndOfInputToken extends Token
{
    /**
     * An "end of input" token identifier
     */
    public const TOKEN_ID = -1;

    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        int $offset
    ) {
        parent::__construct(
            self::TOKEN_ID,
            Channel::EndOfInput()->name,
            Channel::EndOfInput(),
            '',
            $offset,
        );
    }
}
