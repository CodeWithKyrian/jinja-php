<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

/**
 * Abstract base class for all Literal expressions.
 * Should not be instantiated directly.
 */
abstract class Literal extends Expression
{
    public string $type = "Literal";

    public function __construct(public mixed $value)
    {
    }
}