<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis\Result;

/**
 * What an analysis has made of a source.
 *
 * @template-covariant TValue of mixed = mixed
 *
 * @readonly
 */
abstract class Result
{
    /**
     * @readonly
     * @var mixed
     */
    public $value = null;
    /**
     * @param mixed $value
     */
    public function __construct($value = null)
    {
        /**
         * What the source has been built into, or {@see null} in case of the
         * analysis has built nothing.
         *
         * @var TValue
         */
        $this->value = $value;
    }
}
