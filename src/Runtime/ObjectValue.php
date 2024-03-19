<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Runtime;

class ObjectValue extends RuntimeValue
{
    public string $type = "ObjectValue";

    public function __construct(array $value)
    {
        parent::__construct($value);

        $this->builtins = [
            "get" => new FunctionValue(function (StringValue $key, $defaultValue = null) {
                return $this->value[$key->value] ?? $defaultValue ?? new NullValue();
            }),
            "items" => new FunctionValue(function () {
                $items = [];
                foreach ($this->value as $key => $value) {
                    $items[] = new ArrayValue([new StringValue($key), $value]);
                }
                return new ArrayValue($items);
            })
        ];
    }

    public function evaluateAsBool(): BooleanValue
    {
        return new BooleanValue(!empty($this->value));
    }
}
