<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

class SetStatement extends Statement
{
    public string $type = 'Set';

    /**
     * @param Statement[] $body
     */
    public function __construct(
        public Expression $assignee,
        public ?Expression $value,
        public array $body = [],
    ) {
    }
}
