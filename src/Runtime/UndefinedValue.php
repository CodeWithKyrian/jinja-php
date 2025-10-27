<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\Runtime;

/**
 * @extends RuntimeValue<null>
 */
class UndefinedValue extends RuntimeValue
{
    public string $type = 'UndefinedValue';

    public function __construct()
    {
        parent::__construct(null);
    }

    public function jsonSerialize(): mixed
    {
        return 'null';
    }
}
