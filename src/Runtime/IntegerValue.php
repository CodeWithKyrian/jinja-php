<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\Runtime;

/**
 * @extends RuntimeValue<int>
 */
class IntegerValue extends RuntimeValue
{
    public string $type = 'IntegerValue';

    public function __construct(int $value)
    {
        parent::__construct($value);
    }
}
