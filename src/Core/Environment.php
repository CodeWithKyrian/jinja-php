<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Core;

use Codewithkyrian\Jinja\Exceptions\RuntimeException;
use Codewithkyrian\Jinja\Exceptions\SyntaxError;
use Codewithkyrian\Jinja\Runtime\ArrayValue;
use Codewithkyrian\Jinja\Runtime\BooleanValue;
use Codewithkyrian\Jinja\Runtime\FunctionValue;
use Codewithkyrian\Jinja\Runtime\NullValue;
use Codewithkyrian\Jinja\Runtime\NumericValue;
use Codewithkyrian\Jinja\Runtime\ObjectValue;
use Codewithkyrian\Jinja\Runtime\RuntimeValue;
use Codewithkyrian\Jinja\Runtime\StringValue;
use Codewithkyrian\Jinja\Runtime\UndefinedValue;

/**
 * Represents the current environment (scope) at runtime.
 */
class Environment
{
    /**
     * @var array The variables declared in this environment.
     */
    protected array $variables = [];

    /**
     * @var array The tests available in this environment.
     */
    public array $tests = [];

    /**
     * @var ?Environment The parent environment, if any.
     */
    protected ?Environment $parent = null;

    public function __construct(?Environment $parent = null)
    {
        $this->parent = $parent;

        $this->variables = [
            'namespace' => new FunctionValue(function ($args) {
                if (count($args) === 0) {
                    return new ObjectValue([]);
                }
                if (count($args) !== 1 || !($args[0] instanceof ObjectValue)) {
                    throw new RuntimeException("`namespace` expects either zero arguments or a single object argument");
                }
                return $args[0];
            })
        ];

        $this->tests = [
            'boolean' => fn(RuntimeValue $operand) => $operand->type === "BooleanValue",
            'callable' => fn(RuntimeValue $operand) => $operand instanceof FunctionValue,
            'odd' => function (RuntimeValue $operand) {
                if ($operand->type !== "NumericValue") {
                    throw new RuntimeException("Cannot apply test 'odd' to type: $operand->type");
                }
                return $operand->value % 2 !== 0;
            },
            'even' => function (RuntimeValue $operand) {
                if ($operand->type !== "NumericValue") {
                    throw new RuntimeException("Cannot apply test 'even' to type: $operand->type");
                }
                return $operand->value % 2 === 0;
            },
            'false' => fn(RuntimeValue $operand) => $operand->type === "BooleanValue" && !$operand->value,
            'true' => fn(RuntimeValue $operand) => $operand->type === "BooleanValue" && $operand->value,
            'number' => fn(RuntimeValue $operand) => $operand->type === "NumericValue",
            'integer' => function (RuntimeValue $operand) {
                if ($operand->type !== "NumericValue") {
                    return false;
                }
                return is_int($operand->value);
            },
            'iterable' => fn(RuntimeValue $operand) => $operand instanceof ArrayValue || $operand instanceof StringValue,
            'lower' => function (RuntimeValue $operand) {
                if ($operand->type !== "StringValue") {
                    return false;
                }
                return $operand->value === strtolower($operand->value);
            },
            'upper' => function (RuntimeValue $operand) {
                if ($operand->type !== "StringValue") {
                    return false;
                }
                return $operand->value === strtoupper($operand->value);
            },
            'none' => function (RuntimeValue $operand) {
                return $operand->type === "NullValue";
            },
            'defined' => function (RuntimeValue $operand) {
                return $operand->type !== "UndefinedValue";
            },
            'undefined' => function (RuntimeValue $operand) {
                return $operand->type === "UndefinedValue";
            },
            'equalto' => function (RuntimeValue $a, RuntimeValue $b) {
                return $a->value === $b->value;
            },
        ];
    }

    /**
     * Set the value of a variable in the current environment.
     */
    public function set(string $name, $value): RuntimeValue
    {
        return $this->declareVariable($name, self::convertToRuntimeValues($value));
    }

    private function declareVariable(string $name, RuntimeValue $value): RuntimeValue
    {
        if (array_key_exists($name, $this->variables)) {
            throw new SyntaxError("Variable already declared: $name");
        }
        $this->variables[$name] = $value;
        return $value;
    }

    public function setVariable($name, $value)
    {
        $this->variables[$name] = $value;
        return $value;
    }

    /**
     * Resolve the environment in which the variable is declared.
     */
    private function resolve(string $name): Environment
    {
        if (array_key_exists($name, $this->variables)) {
            return $this;
        }

        if ($this->parent !== null) {
            return $this->parent->resolve($name);
        }

        throw new RuntimeException("Unknown variable: $name");
    }

    public function lookupVariable(string $name): RuntimeValue
    {
        try {
            $environment = $this->resolve($name);
            return $environment->variables[$name] ?? new UndefinedValue();
        } catch (\Exception $e) {
            return new UndefinedValue();
        }
    }


    public static function convertToRuntimeValues(mixed $input): RuntimeValue
    {
        if (is_numeric($input)) {
            return new NumericValue($input);
        }

        if (is_string($input)) {
            return new StringValue($input);
        }

        if (is_bool($input)) {
            return new BooleanValue($input);
        }

        if (is_callable($input)) {
            // Wrap the PHP callable in a runtime function value
            return new FunctionValue(function ($args, $scope) use ($input) {
                // Assuming args are also runtime values; extract their inner values for the callable
                $plainArgs = array_map(function ($arg) {
                    return $arg->value;
                }, $args);
                $result = call_user_func_array($input, $plainArgs);
                // Convert the result back to a runtime value
                return $this->convertToRuntimeValues($result);
            });
        }

        if (is_array($input)) {
            if (array_keys($input) !== range(0, count($input) - 1)) {
                // Associative array, treat as ObjectValue
                $convertedItems = [];
                foreach ($input as $key => $value) {
                    $convertedItems[$key] = self::convertToRuntimeValues($value);
                }
                return new ObjectValue($convertedItems);
            } else {
                // Sequential array, treat as ArrayValue
                return new ArrayValue(array_map(self::convertToRuntimeValues(...), $input));
            }
        }

        if (is_callable($input)) {
            return new FunctionValue(function ($args) use ($input) {
                $plainArgs = array_map(function ($arg) {
                    return $arg->value;
                }, $args);
                $result = call_user_func_array($input, $plainArgs);
                // Convert the result back to a runtime value
                return self::convertToRuntimeValues($result);
            });
        }

        if (is_null($input)) {
            return new NullValue();
        }

        throw new RuntimeException("Cannot convert to runtime value: Unsupported type");
    }

}
