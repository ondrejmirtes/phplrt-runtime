<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

/**
 * Recognizes the rule it refers to as many times as the input matches it.
 *
 * The inner rule is read over and over until it stops matching or until the
 * greatest allowed number of times has been reached, and the repetition itself
 * is recognized only in case the least allowed number has been reached as
 * well. A rule that matches without reading anything would repeat forever, so
 * the repetition stops as soon as it stalls.
 *
 * For example, a rule reading one number or more, where #1 recognizes a
 * number:
 * ```php
 * new Repetition(ruleId: 1, min: 1);
 * ```
 *
 * The very same rule written in EBNF, where braces are "zero times or more",
 * so one repetition is written out in front of them:
 * ```ebnf
 * rule = number , { number } ;
 * ```
 *
 * and in pp2:
 * ```pp2
 * Rule : Number()+ ;
 * ```
 *
 * The rule reads the inner one written out as many times in a row as the
 * range allows:
 *
 * ```math
 * L(A^{n,m}) = \bigcup_{i=n}^{m} L(A)^i
 * ```
 *
 * @readonly
 */
final class Repetition implements SequenceInterface
{
    /**
     * @readonly
     */
    public int $ruleId;
    /**
     * @readonly
     */
    public int $min = 0;
    /**
     * @readonly
     * @var int|float
     */
    public $max = \INF;
    /**
     * @param int|float $max
     */
    public function __construct(
        int $ruleId,
        int $min = 0,
        $max = \INF
    ) {
        $this->ruleId = $ruleId;
        /**
         * @var int<0, max>
         */
        $this->min = $min;
        /**
         * @var int<0, max>|float
         */
        $this->max = $max;
        \assert($max >= $min, 'Max repetitions count must be greater or equal than min repetitions');
        \assert(\is_int($max) || \is_infinite($max), 'Max repetitions may contain only integer or INF (float) values');
    }
}
