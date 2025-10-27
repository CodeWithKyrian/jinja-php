<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

use SplObjectStorage;

/**
 * Represents an object literal in the template.
 *
 * @extends Literal<SplObjectStorage<Expression, Expression>>
 */
class ObjectLiteral extends Literal
{
    public string $type = 'ObjectLiteral';

    /**
     * @param SplObjectStorage<Expression, Expression> $value
     */
    public function __construct(SplObjectStorage $value)
    {
        parent::__construct($value);
    }
}
