<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

/**
 * An operation with two sides, separated by the "is" operator.
 */
class TestExpression extends Expression
{
    public string $type = "TestExpression";

    public function __construct(
        public Expression $operand,
        public bool $negate,
        public Identifier $test
    )
    {
    }
}
