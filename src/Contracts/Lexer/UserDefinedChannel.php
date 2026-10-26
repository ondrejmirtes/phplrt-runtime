<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * A channel that is defined outside the basic set of the token channels.
 *
 * @readonly
 */
class UserDefinedChannel implements ChannelInterface
{
    public string $name;
    public function __construct(string $name)
    {
        /**
         * The name of the channel.
         *
         * @var non-empty-string
         */
        $this->name = $name;
    }
}
