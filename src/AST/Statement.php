<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

/**
 * Statements do not result in a value at runtime. They contain one or more expressions internally.
 */
abstract class Statement
{
    public string $type = "Statement";
}