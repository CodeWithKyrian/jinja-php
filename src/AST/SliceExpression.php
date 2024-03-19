<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

class SliceExpression extends Expression
{
    public string $type = "SliceExpression";

    public function __construct(
        public ?Expression $start = null,
        public ?Expression $stop = null,
        public ?Expression $step = null)
    {
    }
}