<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

class TernaryExpression extends Expression
{
    public string $type = 'TernaryExpression';

    public function __construct(public Expression $condition, public Expression $ifTrue, public Expression $ifFalse)
    {
    }
}
