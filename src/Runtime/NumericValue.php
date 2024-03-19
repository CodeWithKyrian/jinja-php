<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Runtime;

class NumericValue extends RuntimeValue {
    public string $type = "NumericValue";

    public function __construct(float|int $value) {
        parent::__construct($value);
    }
}