<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\Runtime;

use Codewithkyrian\Jinja\Core\Environment;

class FunctionValue extends RuntimeValue
{
    public string $type = "FunctionValue";

    public function __construct(callable $value)
    {
        parent::__construct($value);
    }

    public function call(array $args, Environment $env): RuntimeValue
    {
        return call_user_func($this->value, $args, $env);
    }
}
