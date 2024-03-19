<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;
/**
 * Represents an object literal in the template.
 */
class ObjectLiteral extends Literal {
    public string $type = "ObjectLiteral";

    /**
     * @param array<Expression, Expression> $value
     */
    public function __construct(array $value)
    {
        parent::__construct($value);
    }
}