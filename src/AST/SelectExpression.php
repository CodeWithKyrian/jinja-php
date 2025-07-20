<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

class SelectExpression extends Expression
{
    public string $type = "SelectExpression";

    public function __construct(public Expression $lhs, public Expression $test) {}
}
