<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Runtime;

use function Codewithkyrian\Jinja\toTitleCase;

class StringValue extends RuntimeValue
{
    public string $type = "StringValue";

    public function __construct(string $value)
    {
        parent::__construct($value);

        $this->builtins = [
            "upper" => new FunctionValue(fn() => new StringValue(strtoupper($this->value))),
            "lower" => new FunctionValue(fn() => new StringValue(strtolower($this->value))),
            "strip" => new FunctionValue(fn() => new StringValue(trim($this->value))),
            "title" => new FunctionValue(fn() => new StringValue(toTitleCase($this->value))),
            "capitalize" => new FunctionValue(fn() => new StringValue(ucfirst($this->value))),
            "length" => new IntegerValue(strlen($this->value)),
            "rstrip" => new FunctionValue(fn() => new StringValue(rtrim($this->value))),
            "lstrip" => new FunctionValue(fn() => new StringValue(ltrim($this->value))),
            "startswith" => new FunctionValue(function ($args) {
                if (count($args) === 0) {
                    throw new \Exception("startswith() requires at least one argument");
                }

                $prefix = $args[0];

                if ($prefix instanceof StringValue) {
                    return new BooleanValue(str_starts_with($this->value, $prefix->value));
                }

                if ($prefix instanceof ArrayValue) {
                    foreach ($prefix->value as $item) {
                        if (!($item instanceof StringValue)) {
                            throw new \Exception("startswith() tuple elements must be strings");
                        }

                        if (str_starts_with($this->value, $item->value)) {
                            return new BooleanValue(true);
                        }
                    }

                    return new BooleanValue(false);
                }

                throw new \Exception("startswith() must be a string or tuple of strings");
            }),
            "endswith" => new FunctionValue(function ($args) {
                if (count($args) === 0) {
                    throw new \Exception("endswith() requires at least one argument");
                }

                $suffix = $args[0];
                if ($suffix instanceof StringValue) {
                    return new BooleanValue(str_ends_with($this->value, $suffix->value));
                }

                if ($suffix instanceof ArrayValue) {
                    foreach ($suffix->value as $item) {
                        if (!($item instanceof StringValue)) {
                            throw new \Exception("endswith() tuple elements must be strings");
                        }

                        if (str_ends_with($this->value, $item->value)) {
                            return new BooleanValue(true);
                        }
                    }

                    return new BooleanValue(false);
                }

                throw new \Exception("endswith() must be a string or tuple of strings");
            }),
            "split" => new FunctionValue(function ($args) {
                $separator = $args[0] ?? new NullValue();
                if (!($separator instanceof StringValue || $separator instanceof NullValue)) {
                    throw new \Exception("split() separator must be a string or null");
                }

                $maxsplit = $args[1] ?? new IntegerValue(-1);
                if (!($maxsplit instanceof IntegerValue)) {
                    throw new \Exception("split() maxsplit must be an integer");
                }

                return new ArrayValue(
                    array_map(
                        fn($item) => new StringValue($item),
                        explode($separator->value, $this->value, $maxsplit->value)
                    )
                );
            }),
            "replace" => new FunctionValue(function ($args) {
                if (count($args) < 2) {
                    throw new \Exception("replace() requires at least two arguments");
                }

                $oldValue = $args[0];
                $newValue = $args[1];

                if (!($oldValue instanceof StringValue)) {
                    throw new \Exception("replace() old value must be a string");
                }

                if (!($newValue instanceof StringValue)) {
                    throw new \Exception("replace() new value must be a string");
                }

                return new StringValue(str_replace($oldValue->value, $newValue->value, $this->value));
            }),
            "items" => new FunctionValue(function () {
                return new ArrayValue(
                    array_map(
                        fn($value, $key) => new ArrayValue([new StringValue($key), $value]),
                        $this->value,
                        array_keys($this->value)
                    )
                );
            }),
            "keys" => new FunctionValue(function () {
                return new ArrayValue(array_keys($this->value));
            }),
            "values" => new FunctionValue(function () {
                return new ArrayValue(array_values($this->value));
            }),
        ];
    }
}
