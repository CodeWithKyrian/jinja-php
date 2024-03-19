<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

/**
 * Represents an array literal in the template.
 */
class ArrayLiteral extends Literal {
    public string $type = "ArrayLiteral";

    /**
     * @param Expression[] $value
     */
    public function __construct(array $value)
    {
        parent::__construct($value);
    }
}