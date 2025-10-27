<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

class IfStatement extends Statement
{
    public string $type = 'If';

    /**
     * @param Statement[] $body
     * @param Statement[] $alternate
     */
    public function __construct(
        public Expression $test,
        public array $body,
        public array $alternate,
    ) {
    }
}
