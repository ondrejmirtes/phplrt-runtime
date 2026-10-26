<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\ErrorPrinter;

abstract class LexerRuntimeException extends LexerException implements
    RuntimeExceptionInterface
{
    /**
     * @readonly
     */
    public ReadableInterface $source;
    /**
     * @readonly
     */
    public TokenInterface $token;
    public function __construct(
        ReadableInterface $source,
        TokenInterface $token,
        string $message,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        /**
         * Gets the source object in which the error occurred.
         */
        $this->source = $source;
        /**
         * Gets the token on which the error occurred.
         */
        $this->token = $token;
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
