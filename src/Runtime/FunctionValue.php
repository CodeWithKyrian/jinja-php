<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\Runtime;

use Codewithkyrian\Jinja\Core\Environment;

use function call_user_func_array;

/**
 * @extends RuntimeValue<callable(array<RuntimeValue<mixed>>, Environment): RuntimeValue<mixed>>
 */
class FunctionValue extends RuntimeValue
{
    public string $type = 'FunctionValue';

    public function __construct(callable $value)
    {
        parent::__construct($value);
    }

    /**
     * @return RuntimeValue<mixed>
     */
    public function call(array $args, Environment $env): RuntimeValue
    {
        return ($this->value)($args, $env);
    }
}
