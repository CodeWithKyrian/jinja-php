<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Runtime;

class ArrayValue extends RuntimeValue {
    public string $type = "ArrayValue";

    public function __construct(array $value = []) {
        parent::__construct($value);
        $this->builtins['length'] = new NumericValue(count($this->value));
    }

    public function evaluateAsBool(): BooleanValue
    {
        return new BooleanValue(count($this->value) > 0);
    }
}
