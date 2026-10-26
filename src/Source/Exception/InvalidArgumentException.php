<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * An argument that a source cannot accept.
 */
abstract class InvalidArgumentException extends \InvalidArgumentException implements
    SourceExceptionInterface {}
