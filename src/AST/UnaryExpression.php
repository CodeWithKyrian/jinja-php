<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

class UnaryExpression extends Expression
{
    public string $type = "UnaryExpression";

    public function __construct(
        public mixed $operator,
        public Expression $argument
    )
    {
    }
}