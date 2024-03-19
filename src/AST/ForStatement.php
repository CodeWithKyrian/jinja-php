<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

class ForStatement extends Statement
{
    public string $type = "For";

    /**
     * @param Identifier|TupleLiteral $loopvar
     * @param Expression $iterable
     * @param Statement[] $body
     */
    public function __construct(
        public Identifier|TupleLiteral $loopvar,
        public Expression              $iterable,
        public array                   $body
    )
    {
    }
}