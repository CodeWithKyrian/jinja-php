<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

class NullLiteral extends Literal
{
    public string $type = "NullLiteral";

    public function __construct()
    {
        parent::__construct(null);
    }
}
