<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Runtime;

class BooleanValue extends RuntimeValue
{
    public string $type = "BooleanValue";

    public function __construct(bool $value)
    {
        parent::__construct($value);
    }
}
