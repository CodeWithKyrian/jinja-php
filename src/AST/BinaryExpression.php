<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

/**
 * An operation with two sides, separated by an operator.
 * Note: Either side can be a Complex Expression, with order
 * of operations being determined by the operator.
 */
class BinaryExpression extends Expression
{
    public string $type = "BinaryExpression";

    public function __construct(
        public mixed $operator,
        public Expression $left,
        public Expression $right)
    {
    }
}