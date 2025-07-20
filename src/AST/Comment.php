<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

class Comment extends Statement
{
    public string $type = "Comment";

    public function __construct(public string $value) {}
}
