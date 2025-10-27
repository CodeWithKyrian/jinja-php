<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

class FilterStatement extends Statement
{
    public string $type = 'FilterStatement';

    /**
     * @param Statement[] $body
     */
    public function __construct(public Identifier|CallExpression $filter, public array $body)
    {
    }
}
