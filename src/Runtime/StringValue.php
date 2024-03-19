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
            "length" => new NumericValue(strlen($this->value))
        ];
    }

}
