<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Source\Driver\ResourceSourceDriver;
use Phplrt\Source\Driver\SourceDriverInterface;
use Phplrt\Source\Driver\SplFileInfoSourceDriver;
use Phplrt\Source\Driver\StringSourceDriver;
use Phplrt\Source\Exception\NotCreatableException;

/**
 * @readonly
 */
final class SourceFactory implements SourceFactoryInterface
{
    /**
     * @var list<SourceDriverInterface>
     * @readonly
     */
    private array $drivers;

    /**
     * @param iterable<mixed, SourceDriverInterface> $drivers drivers are
     *        queried in the given order and the first one recognizing the
     *        reference wins
     */
    public function __construct(iterable $drivers = [])
    {
        $arrayIsListFunction = function (array $array): bool {
            if (function_exists('array_is_list')) {
                return array_is_list($array);
            }
            if ($array === []) {
                return true;
            }
            $current_key = 0;
            foreach ($array as $key => $noop) {
                if ($key !== $current_key) {
                    return false;
                }
                ++$current_key;
            }
            return true;
        };
        switch (true) {
            case $drivers instanceof \Traversable:
                $this->drivers = \iterator_to_array($drivers, false);
                break;
            case $arrayIsListFunction($drivers):
                $this->drivers = $drivers;
                break;
            default:
                $this->drivers = \array_values($drivers);
                break;
        }
    }

    /**
     * Creates a factory supporting every kind of source reference known to
     * phplrt itself, including the ones that already are a source.
     *
     * @api
     */
    public static function createDefault(): self
    {
        return new self(self::getDefaultDrivers());
    }

    /**
     * Returns default source drivers.
     *
     * @api
     *
     * @return list<SourceDriverInterface>
     */
    public static function getDefaultDrivers(): array
    {
        return [
            new StringSourceDriver(),
            new SplFileInfoSourceDriver(),
            new ResourceSourceDriver(),
        ];
    }

    /**
     * @api
     *
     * @template TArgSource
     * @param mixed $source
     * @return (TArgSource is ReadableInterface ? TArgSource&ReadableInterface : ReadableInterface)
     * @throws NotCreatableException in case none of the drivers recognizes
     *         the source argument
     * @throws SourceExceptionInterface in case of source creation exception occurs
     */
    public function create($source): ReadableInterface
    {
        if ($source instanceof ReadableInterface) {
            return $source;
        }

        foreach ($this->drivers as $driver) {
            $readable = $driver->tryCreate($source);

            if ($readable !== null) {
                return $readable;
            }
        }

        throw NotCreatableException::becauseSourceIsInvalid($source);
    }
}
