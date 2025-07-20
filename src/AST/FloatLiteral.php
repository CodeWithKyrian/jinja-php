<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

class FloatLiteral extends Literal
{
    public string $type = "FloatLiteral";

    public function __construct(float $value)
    {
        parent::__construct($value);
    }
}
