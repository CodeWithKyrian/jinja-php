<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\Runtime;

class IntegerValue extends RuntimeValue
{
    public string $type = "IntegerValue";

    public function __construct(int $value)
    {
        parent::__construct($value);
    }
}
