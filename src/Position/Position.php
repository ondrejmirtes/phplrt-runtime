<?php

declare(strict_types=1);

namespace Phplrt\Position;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Position\Exception\InvalidArgumentException;

/**
 * Implementing a location inside a source made of the line and the column
 * it points at.
 *
 * A position created with no arguments points at the beginning of any source.
 *
 * @readonly
 */
final class Position implements
    PositionInterface
{
    /**
     * @readonly
     */
    public int $line = self::MIN_LINE;
    /**
     * @readonly
     */
    public int $column = self::MIN_COLUMN;
    /**
     * @throws InvalidArgumentException When the line or the column is less
     *         than the minimal allowed one
     */
    public function __construct(
        int $line = self::MIN_LINE,
        int $column = self::MIN_COLUMN
    ) {
        /**
         * The number of the source line the position points at.
         *
         * @var int<1, max>
         */
        $this->line = $line;
        /**
         * The number of the column within its own {@see $line} the position
         * points at.
         *
         * @var int<1, max>
         */
        $this->column = $column;
        if ($line < self::MIN_LINE) {
            throw InvalidArgumentException::becauseLineIsOutOfRange($line);
        }

        if ($column < self::MIN_COLUMN) {
            throw InvalidArgumentException::becauseColumnIsOutOfRange($column);
        }
    }

    public function __toString(): string
    {
        return \sprintf('%d:%d', $this->line, $this->column);
    }
}
