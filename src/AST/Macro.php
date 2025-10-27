<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

class Macro extends Statement
{
    public string $type = 'Macro';

    /**
     * @param Identifier   $name The name of the macro
     * @param Expression[] $args The arguments of the macro
     * @param Statement[]  $body The body of the macro
     */
    public function __construct(public Identifier $name, public array $args, public array $body)
    {
    }
}
