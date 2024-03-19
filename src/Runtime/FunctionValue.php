<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Runtime;

class FunctionValue extends RuntimeValue {
    public string $type = "FunctionValue";

    public function __construct(callable $value) {
        parent::__construct($value);
    }
}
