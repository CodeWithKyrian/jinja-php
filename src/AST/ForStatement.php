<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

/**
 * Loop over each item in a sequence
 * https://jinja.palletsprojects.com/en/3.0.x/templates/#for
 */
class ForStatement extends Statement
{
    public string $type = "For";

    /**
     * @param Identifier|TupleLiteral $loopvar The variable to loop over
     * @param Expression $iterable The iterable to loop over
     * @param Statement[] $body The block to render for each item in the iterable
     * @param Statement[]|null $defaultBlock Optional default block to render if the iterable is empty
     */
    public function __construct(
        public Identifier|TupleLiteral $loopvar,
        public Expression|SelectExpression $iterable,
        public array                   $body,
        public ?array                  $defaultBlock = null
    ) {}
}
