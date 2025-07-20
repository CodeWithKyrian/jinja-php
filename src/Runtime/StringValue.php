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
            "length" => new NumericValue(strlen($this->value)),
            "rstrip" => new FunctionValue(fn() => new StringValue(rtrim($this->value))),
            "lstrip" => new FunctionValue(fn() => new StringValue(ltrim($this->value))),
            "startswith" => new FunctionValue(function ($args) {
                if (count($args) === 0) {
                    throw new \Exception("startswith() requires at least one argument");
                }

                $prefix = $args[0];
                if (!($prefix instanceof StringValue)) {
                    throw new \Exception("startswith() requires a string argument");
                }

                return new BooleanValue(str_starts_with($this->value, $prefix->value));
            }),
            "endswith" => new FunctionValue(function ($args) {
                if (count($args) === 0) {
                    throw new \Exception("endswith() requires at least one argument");
                }

                $suffix = $args[0];
                if (!($suffix instanceof StringValue)) {
                    throw new \Exception("endswith() requires a string argument");
                }

                return new BooleanValue(str_ends_with($this->value, $suffix->value));
            }),
            "split" => new FunctionValue(fn($args) => new ArrayValue(explode($args[0] ?? ' ', $this->value, $args[1] ?? -1))),
        ];
    }
}
