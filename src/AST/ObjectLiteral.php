<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\AST;

use SplObjectStorage;

/**
 * Represents an object literal in the template.
 */
class ObjectLiteral extends Literal
{
    public string $type = "ObjectLiteral";

    /**
     * @param SplObjectStorage $value
     */
    public function __construct(SplObjectStorage $value)
    {
        parent::__construct($value);
    }
}
