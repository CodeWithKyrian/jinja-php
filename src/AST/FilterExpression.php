<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

/**
 * An operation with two sides, separated by the | operator.
 * Operator precedence: https://github.com/pallets/jinja/issues/379#issuecomment-168076202
 */
class FilterExpression extends Expression
{
    public string $type = "FilterExpression";

    public function __construct(
        public Expression $operand,
        public Identifier|CallExpression $filter
    )
    {
    }
}