<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\Runtime;

use JsonSerializable;
use Stringable;

/**
 * @template TValue
 */
abstract class RuntimeValue implements JsonSerializable, Stringable
{
    public string $type = 'RuntimeValue';

    /**
     * A collection of built-in functions for this type.
     */
    public array $builtins = [];

    /**
     * @param TValue $value
     */
    public function __construct(
        public mixed $value,
    ) {
    }

    /**
     * Determines truthiness or falsiness of the runtime value.
     * This function should be overridden by subclasses if it has custom truthiness criteria.
     */
    public function asBool(): BooleanValue
    {
        return new BooleanValue((bool)$this->value);
    }

    public function jsonSerialize(): mixed
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
