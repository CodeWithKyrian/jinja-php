<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

class SpreadExpression extends Expression
{
    public string $type = "SpreadExpression";

    public function __construct(public Expression $argument) {}
}
