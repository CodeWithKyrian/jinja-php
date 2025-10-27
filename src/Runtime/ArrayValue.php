<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\Runtime;

use function count;

/**
 * @extends RuntimeValue<array<RuntimeValue<mixed>>>
 */
class ArrayValue extends RuntimeValue
{
    public string $type = 'ArrayValue';

    public function __construct(array $value = [])
    {
        parent::__construct($value);
        $this->builtins['length'] = new IntegerValue(count($this->value));
    }

    public function asBool(): BooleanValue
    {
        return new BooleanValue(count($this->value) > 0);
    }

    public function __toString(): string
    {
        $encoded = json_encode($this->value, JSON_PRETTY_PRINT);
        if ($encoded === false) {
            return '[unable to encode array]';
        }

        return $encoded;
    }
}
