<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\AST;

class CallStatement extends Statement
{
    public string $type = "CallStatement";

    /**
     * @param CallExpression $call
     * @param Expression[]|null $args
     * @param Statement[] $body
     */
    public function __construct(public CallExpression $call, public ?array $args, public array $body) {}
}
