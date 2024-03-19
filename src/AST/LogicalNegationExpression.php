<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

class LogicalNegationExpression extends Expression
{
    public string $type = "LogicalNegationExpression";
    public function __construct(public Expression $argument)
    {
    }
}