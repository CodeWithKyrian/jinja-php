<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

/**
 * @extends Literal<int>
 */
class IntegerLiteral extends Literal
{
    public string $type = 'IntegerLiteral';

    public function __construct(int $value)
    {
        parent::__construct($value);
    }
}
