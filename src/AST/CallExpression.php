<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

class CallExpression extends Expression
{
    public string $type = 'CallExpression';

    /**
     * @param Expression[] $args
     */
    public function __construct(
        public Expression $callee,
        public array $args,
    ) {
    }
}
