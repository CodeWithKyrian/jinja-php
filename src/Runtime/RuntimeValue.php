<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Runtime;

abstract class RuntimeValue
{
    public string $type = "RuntimeValue";

    /**
     * A collection of built-in functions for this type.
     */
    public array $builtins = [];

    public function __construct(
        public mixed $value
    )
    {
    }

    /**
     * Determines truthiness or falsiness of the runtime value.
     * This function should be overridden by subclasses if it has custom truthiness criteria.
     */
    public function evaluateAsBool() : BooleanValue
    {
        return new BooleanValue(!!$this->value);
    }
}