<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * An arbitrary object that supports reading of source data
 */
abstract class Readable implements ReadableInterface
{
    /**
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data and/or convert it to a string
     */
    abstract public function getContents(): string;

    /**
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data and/or convert it to a string
     */
    public function __toString(): string
    {
        return $this->getContents();
    }

    /**
     * @template TArgSource
     * @param mixed $source
     * @return (TArgSource is ReadableInterface ? TArgSource&ReadableInterface : ReadableInterface)
     * @throws SourceExceptionInterface
     *
     * @deprecated since 4.0, please use "SourceFactory::create()" instead
     * @deprecated 4.0 Please use "SourceFactory::create()" instead
     */
    public static function new($source): ReadableInterface
    {
        return SourceFactory::createDefault()
            ->create($source);
    }

    /**
     * @api
     *
     * @param non-empty-string|null $pathname
     * @return ($pathname is null ? StringSource : VirtualSource)
     *
     * @deprecated since 4.0, please use "StringSource::createEmpty()" or
     *             "VirtualSource::createEmpty()" instead
     * @deprecated 4.0 Please use "StringSource::createEmpty()" or "VirtualSource::createEmpty()" instead
     */
    public static function empty(?string $pathname = null): Readable
    {
        if ($pathname === null) {
            return StringSource::createEmpty();
        }
        return VirtualSource::createEmpty($pathname);
    }

    /**
     * @api
     *
     * @param non-empty-string $pathname
     *
     * @deprecated since 4.0, please use "FileSource::createFromPathname()" instead
     * @deprecated 4.0 Please use "FileSource::createFromPathname()" instead
     */
    public static function fromPathname(string $pathname): FileSource
    {
        return FileSource::createFromPathname($pathname);
    }

    /**
     * @api
     *
     * @deprecated since 4.0, please use "FileSource::createFromSplFileInfo()" instead
     * @deprecated 4.0 Please use "FileSource::createFromSplFileInfo()" instead
     */
    public static function fromSplFileInfo(\SplFileInfo $info): FileSource
    {
        return FileSource::createFromSplFileInfo($info);
    }

    /**
     * @api
     *
     * @param non-empty-string|null $pathname
     * @return ($pathname is null ? StringSource : VirtualSource)
     *
     * @deprecated since 4.0, please use "StringSource::createFromString()" or
     *             "VirtualSource::createFromString()" instead
     * @deprecated 4.0 Please use "StringSource::createFromString()" or "VirtualSource::createFromString()" instead
     */
    public static function fromSources(string $sources, ?string $pathname = null): Readable
    {
        if ($pathname === null) {
            return StringSource::createFromString($sources);
        }
        return VirtualSource::createFromString($pathname, $sources);
    }

    /**
     * @api
     *
     * @param mixed $resource
     * @param non-empty-string|null $pathname
     * @return ($pathname is null ? ResourceSource : VirtualSource)
     *
     * @deprecated since 4.0, please use "ResourceSource::createFromResource()"
     *             or "VirtualSource::createFromResource()" instead
     * @deprecated 4.0 Please use "ResourceSource::createFromResource()" or "VirtualSource::createFromResource()" instead
     */
    public static function fromResource($resource, ?string $pathname = null): Readable
    {
        if ($pathname === null) {
            return ResourceSource::createFromResource($resource);
        }
        return VirtualSource::createFromResourceStream($pathname, $resource);
    }
}
