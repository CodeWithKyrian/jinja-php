<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

/**
 * Represents a numeric constant in the template.
 */
class NumericLiteral extends Literal {
    public string $type = "NumericLiteral";

    public function __construct(float|int $value)
    {
        parent::__construct((int)$value);
    }
}