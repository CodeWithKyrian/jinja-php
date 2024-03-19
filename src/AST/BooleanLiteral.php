<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

/**
 * Represents a boolean constant in the template.
 */
class BooleanLiteral extends Literal {
    public string $type = "BooleanLiteral";

    public function __construct(bool $value)
    {
        parent::__construct($value);
    }
}