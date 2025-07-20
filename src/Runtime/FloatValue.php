<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\Runtime;

class FloatValue extends RuntimeValue
{
    public string $type = "FloatValue";

    public function __construct(float $value)
    {
        parent::__construct($value);
    }
}
