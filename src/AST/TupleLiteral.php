<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

/**
 * Represents a tuple literal in the template.
 */
class TupleLiteral extends Literal {
    public string $type = "TupleLiteral";

    /**
     * @param Expression[] $value
     */
    public function __construct(array $value)
    {
        parent::__construct($value);
    }
}