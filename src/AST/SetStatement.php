<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

class SetStatement extends Statement
{
    public string $type = "Set";

    public function __construct(
        public Expression $assignee,
        public Expression $value
    )
    {
    }
}