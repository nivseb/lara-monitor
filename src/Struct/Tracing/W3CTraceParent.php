<?php

namespace Nivseb\LaraMonitor\Struct\Tracing;

use Nivseb\LaraMonitor\Exceptions\InvalidTraceFormatException;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * @see https://www.w3.org/TR/trace-context/#traceparent-header
 */
class W3CTraceParent implements Stringable, JsonSerializable
{
    public const NO_FLAG        = 0b00000000;
    public const SAMPLE_FLAG    = 0b00000001;
    protected const MATCH_REGEX = '/^(00)-([\da-f]{32})-([\da-f]{16})-(0[01])$/';

    public function __construct(
        public readonly string $version,
        public readonly string $traceId,
        public readonly string $parentId,
        public readonly string $traceFlags
    ) {}

    public function __toString(): string
    {
        return implode('-', [$this->version, $this->traceId, $this->parentId, $this->traceFlags]);
    }

    public function __serialize(): array
    {
        return [(string) $this];
    }

    public function __unserialize(array $data): void
    {
        $parts = [];
        if (!is_string($data[0]) || !preg_match(static::MATCH_REGEX, $data[0], $parts)) {
            throw new InvalidArgumentException('Can`t unserialize string to '.static::class);
        }
        $this->version    = $parts[1];
        $this->traceId    = $parts[2];
        $this->parentId   = $parts[3];
        $this->traceFlags = $parts[4];
    }

    public function sampled(): bool
    {
        return null !== $this->traceFlags
            && (hexdec($this->traceFlags) & static::SAMPLE_FLAG) === static::SAMPLE_FLAG;
    }

    /**
     * @throws InvalidTraceFormatException
     */
    public static function createFromString(string $traceParent): W3CTraceParent
    {
        $parts = [];
        if (!preg_match(static::MATCH_REGEX, $traceParent, $parts)) {
            throw new InvalidTraceFormatException();
        }

        return new self($parts[1], $parts[2], $parts[3], $parts[4]);
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }
}
