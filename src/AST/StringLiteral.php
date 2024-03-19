<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

/**
 * Represents a text constant in the template.
 */
class StringLiteral extends Literal {
    public string $type = "StringLiteral";

    public function __construct(string $value)
    {
        parent::__construct($value);
    }
}