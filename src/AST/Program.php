<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

/**
 * Defines a block which contains many statements. Each chat template corresponds to one Program.
 */
class Program extends Statement
{
    public string $type = "Program";

    /**
     * @param Statement[] $body
     */
    public function __construct(public array $body)
    {
    }
}