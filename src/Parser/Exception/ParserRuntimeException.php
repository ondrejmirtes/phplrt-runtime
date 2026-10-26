<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface as ParserRuntimeExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\ErrorPrinter;

abstract class ParserRuntimeException extends ParserException implements
    ParserRuntimeExceptionInterface
{
    /**
     * @readonly
     */
    public ReadableInterface $source;
    /**
     * @readonly
     */
    public TokenInterface $token;
    /**
     * @readonly
     */
    public ?int $length = null;
    public function __construct(
        ReadableInterface $source,
        TokenInterface $token,
        string $message,
        ?int $length = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        /**
         * The source the error occurred in.
         */
        $this->source = $source;
        $this->token = $token;
        /**
         * The size of the fragment the error occurred in, or {@see null} in
         * case of the error is as large as the token itself.
         *
         * @var int<0, max>|null
         */
        $this->length = $length;
        parent::__construct(
            $message,
            $code,
            $previous,
        );
    }

    public function __toString(): string
    {
        try {
            return (string) (new ErrorPrinter())
                ->print($this);
        } catch (\Throwable $exception) {
            return parent::__toString();
        }
    }
}
