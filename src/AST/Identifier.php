<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

/**
 * Represents a user-defined variable or symbol in the template.
 */
class Identifier extends Expression
{
    public string $type = "Identifier";

    public function __construct(public string $value)
    {
    }
}