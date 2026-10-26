<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Token\Printer\PrettyTokenPrinter;

/**
 * @readonly
 */
class Token implements TokenInterface
{
    public int $id;
    public ?string $name;
    public ChannelInterface $channel;
    public string $value;
    public int $offset = self::MIN_OFFSET;
    public array $captures = [];
    /**
     * @var int<0, max>
     */
    public int $size;

    /**
     * @param int<0, max>|null $size
     */
    public function __construct(
        int $id,
        ?string $name,
        ChannelInterface $channel,
        string $value,
        int $offset = self::MIN_OFFSET,
        array $captures = [],
        /**
         * The size of the fragment the token has been read from, or
         * {@see null} in case of the token is as large as its own value.
         */
        ?int $size = null
    ) {
        $this->id = $id;
        /**
         * @var non-empty-string|null
         */
        $this->name = $name;
        $this->channel = $channel;
        $this->value = $value;
        /**
         * @var int<0, max>
         */
        $this->offset = $offset;
        /**
         * What the subgroups of the token definition have captured, in the
         * order the subgroups are written.
         *
         * A subgroup that has captured nothing is still counted, so a capture
         * is always addressed by the number of its subgroup.
         *
         * @var list<string>
         */
        $this->captures = $captures;
        $this->size = $size ?? \strlen($value);
    }

    public function __toString(): string
    {
        /** @var PrettyTokenPrinter|null $printer */
        static $printer = null;
        $printer ??= new PrettyTokenPrinter();

        return $printer->print($this);
    }
}
