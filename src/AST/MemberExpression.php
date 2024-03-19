<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

class MemberExpression extends Expression
{
    public string $type = "MemberExpression";

    public function __construct(
        public Expression $object,
        public Expression $property,
        public bool $computed)
    {
    }
}