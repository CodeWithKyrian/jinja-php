<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

class KeywordArgumentExpression extends Expression
{
    public string $type = "KeywordArgumentExpression";

    public function __construct(
        public Identifier $key,
        public Expression $value
    )
    {
    }
}