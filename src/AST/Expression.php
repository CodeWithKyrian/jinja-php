<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

/**
 * Expressions will result in a value at runtime (unlike statements).
 */
abstract class Expression extends Statement {
    public string $type = "Expression";
}