<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\Runtime;

use RuntimeException;

/**
 * @extends RuntimeValue<array<string, RuntimeValue<mixed>>>
 */
class ObjectValue extends RuntimeValue
{
    public string $type = 'ObjectValue';

    public function __construct(array $value)
    {
        parent::__construct($value);

        $this->builtins = [
            'get' => new FunctionValue(function ($args) {
                $key          = $args[0];
                $defaultValue = $args[1] ?? new NullValue();

                if (!($key instanceof StringValue)) {
                    throw new RuntimeException("Object key must be a string: got {$key->type}");
                }

                return $this->value[$key->value] ?? $defaultValue;
            }),
            'items' => new FunctionValue(function () {
                $items = [];
                foreach ($this->value as $key => $value) {
                    $items[] = new ArrayValue([new StringValue($key), $value]);
                }
                return new ArrayValue($items);
            }),
            'keys' => new FunctionValue(function () {
                $keys = [];
                foreach ($this->value as $key => $value) {
                    $keys[] = new StringValue($key);
                }
                return new ArrayValue($keys);
            }),
            'values' => new FunctionValue(function () {
                return new ArrayValue(array_values($this->value));
            }),
        ];
    }

    public function asBool(): BooleanValue
    {
        return new BooleanValue(!empty($this->value));
    }

    public function __toString(): string
    {
        $encoded = json_encode($this->value, JSON_PRETTY_PRINT);
        if ($encoded === false) {
            return '[unable to encode object]';
        }

        return $encoded;
    }
}
