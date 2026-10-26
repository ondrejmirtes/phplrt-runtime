<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\FailureResult;
use Phplrt\Parser\Analysis\Result\PartialResult;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;
use Phplrt\Parser\Exception\LexicalErrorException;
use Phplrt\Parser\Exception\ParserException;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Parser\Exception\ParserSourceException;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Exception\UnknownInitialRuleException;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Buffer\ArrayBuffer;
use Phplrt\Parser\Internal\Buffer\BufferInterface;
use Phplrt\Parser\Internal\MessageInterpolator;
use Phplrt\Parser\Internal\Reduction\TraceReducer;
use Phplrt\Parser\Internal\Tracing\RecursiveDescentTracer;
use Phplrt\Parser\Internal\Tracing\Result\FailureTracingResult;
use Phplrt\Parser\Internal\Tracing\Result\SuccessfulTracingResult;
use Phplrt\Parser\Internal\Tracing\Result\TracingResult;

/**
 * @template TResult of mixed = mixed
 *
 * @template-implements ParserInterface<TResult>
 *
 * @phpstan-type ExpectationsTableType array<int, non-empty-string>
 *
 * @phpstan-import-type ReducerType from TraceReducer
 * @phpstan-import-type KeptTableType from RecursiveDescentTracer
 * @phpstan-import-type StartPredictionTableType from RecursiveDescentTracer
 * @phpstan-import-type ChoicePredictionTableType from RecursiveDescentTracer
 * @phpstan-import-type SequencePredictionTableType from RecursiveDescentTracer
 * @phpstan-import-type MessageTableType from RecursiveDescentTracer
 *
 * @readonly
 */
class Parser implements ParserInterface
{
    /**
     * @readonly
     */
    private LexerInterface $lexer;
    /**
     * @readonly
     */
    private array $grammar;
    /**
     * @readonly
     */
    private array $expectations = [];
    /**
     * @readonly
     */
    private array $messages = [];
    /**
     * Recognizes a source against the grammar.
     * @readonly
     */
    private RecursiveDescentTracer $tracer;

    /**
     * Builds what has been recognized into the values the grammar
     * describes.
     * @readonly
     */
    private TraceReducer $reducer;

    /**
     * The identifier of the rule the analysis starts at.
     *
     * @var int<0, max>
     *
     * @phpstan-readonly-allow-private-mutation
     */
    private int $initial;

    /**
     * @param int<0, max> $initial the identifier of the rule the analysis
     *        starts at
     * @param array<int<0, max>, ReducerType> $reducers
     * @param KeptTableType $kept the rule identifiers that become a node of
     *        the result
     * @param StartPredictionTableType $startPrediction the tokens a rule may
     *        begin with, or {@see null} for a rule that may begin with any
     *        of them
     * @param ChoicePredictionTableType $choicePrediction the alternatives
     *        of every alternation worth trying, indexed by the token the
     *        reading is at
     * @param SequencePredictionTableType $sequencePrediction the elements of
     *        every sequence read in place: an element that may be left out
     *        written as the rule it wraps, negated, and a negative predicate
     *        written as itself, negated
     * @param StartPredictionTableType|null $lookahead the former name of
     *        {@see $startPrediction}. Please do not use it anymore.
     */
    public function __construct(
        LexerInterface $lexer,
        array $grammar,
        int $initial,
        array $reducers = [],
        array $expectations = [],
        array $messages = [],
        array $kept = [],
        array $startPrediction = [],
        array $choicePrediction = [],
        array $sequencePrediction = [],
        ?array $lookahead = null
    ) {
        $this->lexer = $lexer;
        /**
         * The rules the reading is described by.
         *
         * @var list<RuleInterface>
         */
        $this->grammar = $grammar;
        /**
         * The way an error has to name each token: by its name, or by what an
         * anonymous one is recognized by
         *
         * @var ExpectationsTableType
         */
        $this->expectations = $expectations;
        /**
         * The message describing the failure of a rule, indexed by the
         * rule identifiers
         *
         * @var MessageTableType
         */
        $this->messages = $messages;
        $this->initial = $initial;

        $this->tracer = new RecursiveDescentTracer(
            $grammar,
            $messages,
            $kept,
            $lookahead ?? $startPrediction,
            $choicePrediction,
            $sequencePrediction,
        );

        $this->reducer = new TraceReducer(
            $grammar,
            $reducers,
        );
    }

    /**
     * Returns the parser starting the analysis at the given rule.
     *
     * @api
     *
     * @param int<0, max> $rule
     * @throws UnknownInitialRuleException in case of the analysis may not be
     *         started at the given rule
     * @return static
     */
    public function withInitial(int $rule)
    {
        if (!isset($this->grammar[$rule])) {
            throw UnknownInitialRuleException::becauseRuleIsNotDefined($rule);
        }

        $self = clone $this;
        $self->initial = $rule;

        return $self;
    }

    /**
     * Reads as much of the source as the grammar describes and reports what it
     * has made of it.
     *
     * Nothing about the source is an error: how far the grammar goes is told
     * by the class of the result, and what stands in the way by the error it
     * carries.
     *
     * @return ($mode is Mode::Tolerant|null
     *      ? (SuccessfulResult<TResult>|FailureResult)
     *      : (SuccessfulResult<null>|FailureResult))
     * @throws ParserSourceException in case of the source cannot be read
     * @throws LexicalErrorException in case of the source cannot be split
     *         into tokens
     * @throws ParserException in case of the lexer fails for any other reason
     */
    public function analyze(ReadableInterface $source, ?Mode $mode = null)
    {
        $mode ??= Mode::Tolerant();
        $result = $this->trace($source);

        if (!$result instanceof FailureTracingResult) {
            /** @var SuccessfulResult<TResult>|SuccessfulResult<null> */
            return new SuccessfulResult(
                $this->reduce($source, $result, $mode),
            );
        }

        $error = $this->createLazyException($source, $result);

        // A grammar that has read nothing has built nothing either, so there is
        // no fragment to report, and the source is only described by the error
        if ($result->length === 0) {
            return new FailureResult(
                $result->token ?? $result->stoppedAt,
                $error,
            );
        }

        /** @var PartialResult<TResult>|PartialResult<null> */
        return new PartialResult(
            $this->reduce($source, $result, $mode),
            $result->stoppedAt,
            $error,
        );
    }

    /**
     * @return mixed
     */
    public function parse(ReadableInterface $source)
    {
        $result = $this->trace($source);

        if ($result instanceof FailureTracingResult) {
            throw $this->createException($source, $result);
        }

        return $this->reduce($source, $result, Mode::Tolerant());
    }

    /**
     * @return ($mode is Mode::SyntaxCheck ? null : TResult)
     */
    private function reduce(ReadableInterface $source, TracingResult $result, Mode $mode)
    {
        if ($mode === Mode::SyntaxCheck()) {
            return null;
        }

        return $this->reducer->reduce($result, $source, $this->initial);
    }

    /**
     * @return \Closure(): ParserRuntimeException
     */
    private function createLazyException(ReadableInterface $source, FailureTracingResult $result): \Closure
    {
        return function () use ($source, $result): ParserRuntimeException {
            return $this->createException($source, $result);
        };
    }

    private function createException(ReadableInterface $source, FailureTracingResult $result): ParserRuntimeException
    {
        $expected = [];

        foreach ($result->expected as $tokenId) {
            $expectation = $this->expectations[$tokenId] ?? null;

            if ($expectation !== null) {
                $expected[] = $expectation;
            }
        }

        $token = $result->token ?? $result->stoppedAt;

        $rule = $result->labelled;
        $message = $rule === null ? null : $this->messages[$rule] ?? null;

        // The grammar says nothing about this failure, so it is described by
        // the tokens that could have been read instead
        if ($rule === null || $message === null) {
            return UnexpectedTokenException::becauseUnexpectedTokenProduced(
                $source,
                $token,
                $expected,
            );
        }

        return UnexpectedTokenException::becauseGrammarDescribesTheError(
            $source,
            $token,
            (new MessageInterpolator())
                ->interpolate($message, $source, $token, $expected),
            $expected,
            $rule,
        );
    }

    /**
     * @return \Phplrt\Parser\Internal\Tracing\Result\SuccessfulTracingResult|\Phplrt\Parser\Internal\Tracing\Result\FailureTracingResult
     */
    private function trace(ReadableInterface $source)
    {
        $buffer = $this->lex($source);

        return $this->tracer->trace($buffer, $this->initial);
    }

    /**
     * Which tokens reach the grammar is decided by the lexer: a token it does
     * not report is a token the grammar is not written in terms of.
     *
     * The lexer is a part of the parser, so whatever it fails with is reported
     * as a failure of the parser.
     *
     * @throws ParserSourceException in case of the source cannot be read
     * @throws LexicalErrorException in case of the source cannot be split
     *         into tokens
     * @throws ParserException in case of the lexer fails for any other reason
     */
    private function lex(ReadableInterface $source): BufferInterface
    {
        try {
            return new ArrayBuffer($this->lexer->lex($source));
        } catch (LexerRuntimeExceptionInterface $e) {
            throw LexicalErrorException::becauseSourceIsNotTokenizable($e);
        } catch (LexerExceptionInterface $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof SourceExceptionInterface) {
                throw ParserSourceException::becauseSourceIsNotReadable($previous);
            }

            throw new ParserException($e->getMessage(), 0, $e);
        }
    }
}
