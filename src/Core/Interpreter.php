<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\Core;

use Codewithkyrian\Jinja\AST\BinaryExpression;
use Codewithkyrian\Jinja\AST\CallExpression;
use Codewithkyrian\Jinja\AST\Expression;
use Codewithkyrian\Jinja\AST\FilterExpression;
use Codewithkyrian\Jinja\AST\ForStatement;
use Codewithkyrian\Jinja\AST\Identifier;
use Codewithkyrian\Jinja\AST\IfStatement;
use Codewithkyrian\Jinja\AST\KeywordArgumentExpression;
use Codewithkyrian\Jinja\AST\Macro;
use Codewithkyrian\Jinja\AST\MemberExpression;
use Codewithkyrian\Jinja\AST\Program;
use Codewithkyrian\Jinja\AST\SelectExpression;
use Codewithkyrian\Jinja\AST\SetStatement;
use Codewithkyrian\Jinja\AST\SliceExpression;
use Codewithkyrian\Jinja\AST\Statement;
use Codewithkyrian\Jinja\AST\TestExpression;
use Codewithkyrian\Jinja\AST\TupleLiteral;
use Codewithkyrian\Jinja\AST\UnaryExpression;
use Codewithkyrian\Jinja\Exceptions\RuntimeException;
use Codewithkyrian\Jinja\Exceptions\SyntaxError;
use Codewithkyrian\Jinja\Runtime\ArrayValue;
use Codewithkyrian\Jinja\Runtime\BooleanValue;
use Codewithkyrian\Jinja\Runtime\FunctionValue;
use Codewithkyrian\Jinja\Runtime\KeywordArgumentsValue;
use Codewithkyrian\Jinja\Runtime\NullValue;
use Codewithkyrian\Jinja\Runtime\NumericValue;
use Codewithkyrian\Jinja\Runtime\ObjectValue;
use Codewithkyrian\Jinja\Runtime\RuntimeValue;
use Codewithkyrian\Jinja\Runtime\StringValue;
use Codewithkyrian\Jinja\Runtime\TupleValue;
use Codewithkyrian\Jinja\Runtime\UndefinedValue;
use function Codewithkyrian\Jinja\array_some;
use function Codewithkyrian\Jinja\slice;
use function Codewithkyrian\Jinja\toTitleCase;

class Interpreter
{
    private Environment $global;

    public function __construct(?Environment $env = null)
    {
        $this->global = $env ?? new Environment();
    }

    public function run($program): RuntimeValue
    {
        return $this->evaluate($program, $this->global);
    }

    function evaluate(?Statement $statement, Environment $environment): RuntimeValue
    {
        if ($statement === null) return new UndefinedValue();

        switch ($statement->type) {
            case "Program":
                return $this->evalProgram($statement, $environment);

            case "Set":
                return $this->evaluateSet($statement, $environment);

            case "If":
                return $this->evaluateIf($statement, $environment);

            case "For":
                return $this->evaluateFor($statement, $environment);

            case "Macro":
                return $this->evaluateMacro($statement, $environment);

            case "NumericLiteral":
                return new NumericValue($statement->value);

            case "StringLiteral":
                return new StringValue($statement->value);

            case "BooleanLiteral":
                return new BooleanValue($statement->value);

            case "NullLiteral":
                return new NullValue();

            case "ArrayLiteral":
                $values = array_map(function ($x) use ($environment) {
                    return $this->evaluate($x, $environment);
                }, $statement->value);
                return new ArrayValue($values);

            case "TupleLiteral":
                $values = array_map(function ($x) use ($environment) {
                    return $this->evaluate($x, $environment);
                }, $statement->value);
                return new TupleValue($values);

            case "ObjectLiteral":
                $mapping = [];
                foreach ($statement->value as $key => $value) {
                    $evaluatedKey = $this->evaluate($key, $environment);
                    if (!($evaluatedKey instanceof StringValue)) {
                        throw new RuntimeException("Object keys must be strings");
                    }
                    $mapping[$evaluatedKey->value] = $this->evaluate($value, $environment);
                }
                return new ObjectValue($mapping);

            case "Identifier":
                return $this->evaluateIdentifier($statement, $environment);

            case "CallExpression":
                return $this->evaluateCallExpression($statement, $environment);

            case "MemberExpression":
                return $this->evaluateMemberExpression($statement, $environment);

            case "UnaryExpression":
                return $this->evaluateUnaryExpression($statement, $environment);

            case "BinaryExpression":
                return $this->evaluateBinaryExpression($statement, $environment);

            case "FilterExpression":
                return $this->evaluateFilterExpression($statement, $environment);

            case "TestExpression":
                return $this->evaluateTestExpression($statement, $environment);

            default:
                throw new RuntimeException("Unknown node type: " . $statement->type);
        }
    }


    /**
     * Evaluates expressions following the binary operation type.
     * @throws SyntaxError
     */
    private function evaluateBinaryExpression(BinaryExpression $node, Environment $environment): RuntimeValue
    {
        $left = $this->evaluate($node->left, $environment);

        // Logical operators with short-circuiting handled by the evaluate function
        switch ($node->operator->value) {
            case "and":
                return $left->evaluateAsBool()->value ? $this->evaluate($node->right, $environment) : $left;
            case "or":
                return $left->evaluateAsBool()->value ? $left : $this->evaluate($node->right, $environment);
        }

        // For non-short-circuit operators, evaluate the right operand
        $right = $this->evaluate($node->right, $environment);

        // Equality operators
        switch ($node->operator->value) {
            case "==":
                return new BooleanValue($left->value == $right->value);
            case "!=":
                return new BooleanValue($left->value != $right->value);
        }

        // Check for operation on undefined or null values
        if ($left instanceof UndefinedValue || $right instanceof UndefinedValue) {
            throw new \Exception("Cannot perform operation on undefined values");
        }

        if ($left instanceof NullValue || $right instanceof NullValue) {
            throw new \Exception("Cannot perform operation on null values");
        }

        // Numeric operations
        if ($left instanceof NumericValue && $right instanceof NumericValue) {
            switch ($node->operator->value) {
                // Arithmetic operators
                case "+":
                    return new NumericValue($left->value + $right->value);
                case "-":
                    return new NumericValue($left->value - $right->value);
                case "*":
                    return new NumericValue($left->value * $right->value);
                case "/":
                    return new NumericValue($left->value / $right->value);
                case "%":
                    return new NumericValue($left->value % $right->value);

                    // Comparison operators
                case "<":
                    return new BooleanValue($left->value < $right->value);
                case ">":
                    return new BooleanValue($left->value > $right->value);
                case ">=":
                    return new BooleanValue($left->value >= $right->value);
                case "<=":
                    return new BooleanValue($left->value <= $right->value);
            }
        }

        // Array operations
        if ($left instanceof ArrayValue && $right instanceof ArrayValue && $node->operator->value === "+") {
            // Concatenate arrays
            return new ArrayValue(array_merge($left->value, $right->value));
        }

        if ($right instanceof ArrayValue) {
            $memberFound = false;
            foreach ($right->value as $item) {
                if ($item->value === $left->value) {
                    $memberFound = true;
                    break;
                }
            }
            switch ($node->operator->value) {
                case "in":
                    return new BooleanValue($memberFound);
                case "not in":
                    return new BooleanValue(!$memberFound);
            }
        }

        if ($left instanceof StringValue || $right instanceof StringValue) {
            // Ensure both operands are treated as strings for concatenation
            if ($node->operator->value == "+") {
                $leftStr = ($left instanceof StringValue) ? $left->value : (string)$left->value;
                $rightStr = ($right instanceof StringValue) ? $right->value : (string)$right->value;
                return new StringValue($leftStr . $rightStr);
            }
        }

        if ($left instanceof StringValue && $right instanceof StringValue) {
            switch ($node->operator->value) {
                case "in":
                    return new BooleanValue(str_contains($right->value, $left->value));
                case "not in":
                    return new BooleanValue(!str_contains($right->value, $left->value));
            }
        }

        if ($left instanceof StringValue && $right instanceof ObjectValue) {
            switch ($node->operator->value) {
                case "in":
                    return new BooleanValue(array_key_exists($left->value, $right->value));
                case "not in":
                    return new BooleanValue(!array_key_exists($left->value, $right->value));
            }
        }

        throw new SyntaxError("Unknown operator: " . $node->operator->value);
    }

    /**
     * Evaluates the arguments of a call expression.
     * @param Expression[] $args
     * @param Environment $environment
     * @return array<RuntimeValue[], array<string, RuntimeValue>>
     */
    private function evaluateArguments(array $args, Environment $environment): array
    {
        $positionalArguments = [];
        $keywordArguments = [];

        foreach ($args as $argument) {
            if ($argument instanceof KeywordArgumentExpression) {
                $keywordArguments[$argument->key->value] = $this->evaluate($argument->value, $environment);
            } else {
                if (count($keywordArguments) > 0) {
                    throw new \Exception("Positional arguments must come before keyword arguments");
                }
                $positionalArguments[] = $this->evaluate($argument, $environment);
            }
        }

        return [$positionalArguments, $keywordArguments];
    }


    /**
     * Evaluates expressions following the filter operation type.
     * @throws SyntaxError
     * @throws \Exception
     */
    private function evaluateFilterExpression(FilterExpression $node, Environment $environment): RuntimeValue
    {
        $operand = $this->evaluate($node->operand, $environment);

        if ($node->filter instanceof Identifier) {
            if ($node->filter->value === 'tojson') {
                return new StringValue(json_encode($operand));
            }

            if ($operand instanceof ArrayValue) {
                switch ($node->filter->value) {
                    case "list":
                        return $operand;
                    case "first":
                        return $operand->value[0];
                    case "last":
                        $lastIndex = count($operand->value) - 1;
                        return $operand->value[$lastIndex];
                    case "length":
                        return new NumericValue(count($operand->value));
                    case "reverse":
                        $reversed = array_reverse($operand->value);
                        return new ArrayValue($reversed);
                    case "sort":
                        usort($operand->value, function (RuntimeValue $a, RuntimeValue $b) {
                            if ($a->type != $b->type) {
                                throw new \Exception("Cannot compare different types: $a->type and $b->type");
                            }

                            return match ($a->type) {
                                "NumericValue" => $a->value <=> $b->value,
                                "StringValue" => strcmp($a->value, $b->value),
                                default => throw new \Exception("Cannot compare type: $a->type"),
                            };
                        });
                        return new ArrayValue($operand->value);
                    default:
                        throw new \Exception("Unknown ArrayValue filter: {$node->filter->value}");
                }
            }

            // StringValue filters
            if ($operand instanceof StringValue) {
                return match ($node->filter->value) {
                    "length" => new NumericValue(strlen($operand->value)),
                    "upper" => new StringValue(strtoupper($operand->value)),
                    "lower" => new StringValue(strtolower($operand->value)),
                    "title" => new StringValue(toTitleCase($operand->value)),
                    "capitalize" => new StringValue(ucfirst($operand->value)),
                    "trim" => new StringValue(trim($operand->value)),
                    "indent" => new StringValue(
                        implode(
                            "\n",
                            array_map(function ($x, $i) {
                                // By default, don't indent the first line or empty lines
                                return $i === 0 || $x === "" ? $x : "    " . $x;
                            }, explode("\n", $operand->value), range(0, count(explode("\n", $operand->value)) - 1))
                        )
                    ),
                    "string" => $operand,
                    default => throw new \Exception("Unknown StringValue filter: {$node->filter->value}"),
                };
            }

            // NumericValue filters
            if ($operand instanceof NumericValue) {
                return match ($node->filter->value) {
                    "abs" => new NumericValue(abs($operand->value)),
                    default => throw new \Exception("Unknown NumericValue filter: {$node->filter->value}"),
                };
            }

            // ObjectValue filters
            if ($operand instanceof ObjectValue) {
                switch ($node->filter->value) {
                    case "items":
                        $items = [];
                        foreach ($operand->value as $key => $value) {
                            $items[] = new ArrayValue([new StringValue($key), $value]);
                        }
                        return new ArrayValue($items);
                    case "length":
                        return new NumericValue(count($operand->value));
                    default:
                        throw new \Exception("Unknown ObjectValue filter: {$node->filter->value}");
                }
            }

            throw new \Exception("Cannot apply filter {$node->filter->value} to type $operand->type");
        }


        if ($node->filter instanceof CallExpression) {
            /** @var CallExpression $filter */
            $filter = $node->filter;

            if ($filter->callee->type !== "Identifier") {
                throw new \Exception("Unknown filter: {$filter->callee->type}");
            }

            $filterName = $filter->callee instanceof Identifier ? $filter->callee->value : throw new \Exception("Unknown filter: {$filter->callee->type}");

            if ($filterName === "tojson") {
                return new StringValue(json_encode($operand));
            } elseif ($filterName === "join") {
                if ($operand instanceof StringValue) {
                    $value = mb_str_split($operand->value);
                } elseif ($operand instanceof ArrayValue) {
                    $value = $operand->value;
                } else {
                    throw new \Exception("Cannot apply join filter to type: $operand->type");
                }

                [$args, $kwargs] = $this->evaluateArguments($filter->args, $environment);
                $separator = $args[0] ?? $kwargs["separator"] ?? new StringValue("");

                if (!($separator instanceof StringValue)) {
                    throw new \Exception("separator must be a string");
                }

                return new StringValue(implode($separator->value, $value));
            }

            if ($operand instanceof ArrayValue) {
                switch ($filterName) {
                    case "selectattr":
                    case "rejectattr":
                        if (array_some($operand->value, fn($x) => !($x instanceof ObjectValue))) {
                            throw new \Exception("`$filterName` can only be applied to an array of objects");
                        }

                        if (array_some($filter->args, fn($x) => $x->type !== "StringLiteral")) {
                            throw new \Exception("The arguments of `$filterName` must be strings");
                        }

                        [$attrExpr, $testNameExpr, $valueExpr] = $filter->args;

                        $attr = $this->evaluate($attrExpr, $environment);
                        $testName = isset($testNameExpr) ? $this->evaluate($testNameExpr, $environment) : null;
                        $value = isset($valueExpr) ? $this->evaluate($valueExpr, $environment) : null;

                        if (!($attr instanceof StringValue)) {
                            throw new \Exception("The attribute name of `$filterName` must be a string");
                        }

                        $testFunction = null;
                        if ($testName !== null) {
                            $test = $environment->tests[$testName->value] ?? null;
                            if ($test === null) {
                                throw new \Exception("Unknown test: " . $testName->value);
                            }
                            $testFunction = $test;
                        } else {
                            // Default to checking for truthiness if no test name is provided
                            $testFunction = fn($x) => $x->evaluateAsBool()->value;
                        }

                        $filtered = [];
                        foreach ($operand->value as $item) {
                            $attrValue = $item->value[$attr->value] ?? null;
                            if ($attrValue === null) {
                                continue;
                            }

                            $testResult = $testFunction($attrValue, $value);
                            $shouldInclude = ($filterName === "selectattr") ? $testResult : !$testResult;

                            if ($shouldInclude) {
                                $filtered[] = $item;
                            }
                        }

                        return new ArrayValue($filtered);

                    case "map":
                        [$_, $kwargs] = $this->evaluateArguments($filter->args, $environment);

                        if (array_key_exists("attribute", $kwargs)) {
                            $attr = $kwargs["attribute"];
                            if (!($attr instanceof StringValue)) {
                                throw new \Exception("attribute must be a string");
                            }

                            $defaultValue = $kwargs["default"] ?? new UndefinedValue();

                            $mapped = array_map(function ($item) use ($attr, $defaultValue) {
                                if (!($item instanceof ObjectValue)) {
                                    throw new \Exception("items in map must be an object");
                                }

                                return $item->value[$attr->value] ?? $defaultValue;
                            }, $operand->value);

                            return new ArrayValue($mapped);
                        } else {
                            throw new \Exception("`map` expressions without `attribute` set are not currently supported.");
                        }

                    default:
                        throw new \Exception("Unknown ArrayValue filter: $filterName");
                }
            } elseif ($operand instanceof StringValue) {
                switch ($filterName) {
                    case "indent":
                        [$args, $kwargs] = $this->evaluateArguments($filter->args, $environment);

                        $width = $args[0] ?? $kwargs["width"] ?? new NumericValue(4);
                        if (!($width instanceof NumericValue)) {
                            throw new \Exception("width must be a number");
                        }

                        $first = $args[1] ?? $kwargs["first"] ?? new BooleanValue(false);
                        $blank = $args[2] ?? $kwargs["blank"] ?? new BooleanValue(false);

                        $lines = explode("\n", $operand->value);
                        $indent = str_repeat(" ", $width->value);
                        $indented = array_map(function ($x, $i) use ($first, $blank, $indent) {
                            return (!($first->value) && $i === 0) || (!($blank->value) && $x === "") ? $x : $indent . $x;
                        }, $lines, range(0, count($lines) - 1));

                        return new StringValue(implode("\n", $indented));

                    default:
                        throw new \Exception("Unknown StringValue filter: $filterName");
                }
            } else {
                throw new \Exception("Cannot apply filter \"$filterName\" to type: $operand->type");
            }
        }

        throw new \Exception("Unknown filter: {$node->filter->type}");
    }

    /**
     * Evaluates expressions following the test operation type.
     */
    private function evaluateTestExpression(TestExpression $node, Environment $environment): BooleanValue
    {
        $operand = $this->evaluate($node->operand, $environment);

        $testFunction = $environment->tests[$node->test->value] ?? null;

        if ($testFunction === null) {
            throw new \Exception("Unknown test: {$node->test->value}");
        }

        $result = $testFunction($operand);

        return new BooleanValue($node->negate ? !$result : $result);
    }

    /**
     * Evaluates expressions following the unary operation type.
     */
    private function evaluateUnaryExpression(UnaryExpression $node, Environment $environment): RuntimeValue
    {
        $argument = $this->evaluate($node->argument, $environment);

        return match ($node->operator->value) {
            "not" => new BooleanValue(!$argument->value),
            default => throw new \Exception("Unknown operator: {$node->operator->value}"),
        };
    }

    private function evalProgram(Program $program, Environment $environment): StringValue
    {
        return $this->evaluateBlock($program->body, $environment);
    }

    /**
     * @param Statement[] $statements
     */
    private function evaluateBlock(array $statements, Environment $environment): StringValue
    {
        $result = "";
        foreach ($statements as $statement) {
            $lastEvaluated = $this->evaluate($statement, $environment);
            if (!($lastEvaluated instanceof NullValue) && !($lastEvaluated instanceof UndefinedValue)) {
                $result .= $lastEvaluated->value;
            }
        }
        return new StringValue($result);
    }

    private function evaluateIdentifier(Identifier $node, Environment $environment): RuntimeValue
    {
        return $environment->lookupVariable($node->value);
    }

    private function evaluateCallExpression(CallExpression $expr, Environment $environment): RuntimeValue
    {
        [$args, $kwargs] = $this->evaluateArguments($expr->args, $environment);

        if (!empty($kwargs)) {
            $args[] = new KeywordArgumentsValue($kwargs);
        }

        $fn = $this->evaluate($expr->callee, $environment);
        if (!($fn instanceof FunctionValue)) {
            throw new RuntimeException("Cannot call something that is not a function: got $fn->type");
        }
        return call_user_func($fn->value, $args, $environment);
    }

    private function evaluateSliceExpression(RuntimeValue $object, SliceExpression $expr, Environment $environment): ArrayValue|StringValue
    {
        if (!($object instanceof ArrayValue || $object instanceof StringValue)) {
            throw new RuntimeException("Slice object must be an array or string");
        }

        $start = $this->evaluate($expr->start, $environment);
        $stop = $this->evaluate($expr->stop, $environment);
        $step = $this->evaluate($expr->step, $environment);

        // Validate arguments
        if (!($start instanceof NumericValue || $start instanceof UndefinedValue)) {
            throw new RuntimeException("Slice start must be numeric or undefined");
        }
        if (!($stop instanceof NumericValue || $stop instanceof UndefinedValue)) {
            throw new RuntimeException("Slice stop must be numeric or undefined");
        }
        if (!($step instanceof NumericValue || $step instanceof UndefinedValue)) {
            throw new RuntimeException("Slice step must be numeric or undefined");
        }

        if ($object instanceof ArrayValue) {
            $sliced = slice($object->value, $start->value, $stop->value, $step->value);
            return new ArrayValue($sliced);
        } else {
            $sliced = slice($object->value, $start->value, $stop->value, $step->value);
            return new StringValue(implode("", $sliced));
        }
    }

    private function evaluateMemberExpression(MemberExpression $expr, Environment $environment): RuntimeValue
    {
        $object = $this->evaluate($expr->object, $environment);

        if ($expr->computed) {
            if ($expr->property->type == "SliceExpression") {
                return $this->evaluateSliceExpression($object, $expr->property, $environment);
            } else {
                $property = $this->evaluate($expr->property, $environment);
            }
        } else {
            $property = new StringValue($expr->property->value);
        }

        if ($object instanceof ObjectValue) {
            if (!($property instanceof StringValue)) {
                throw new RuntimeException("Cannot access property with non-string: got {$property->type}");
            }
            $value = $object->value[$property->value] ?? $object->builtins[$property->value];
        } else if ($object instanceof ArrayValue || $object instanceof StringValue) {
            if ($property instanceof NumericValue) {
                $index = $property->value;
                $length = count($object->value);

                // Handle negative indices (Python-style)
                if ($index < 0) {
                    $index = $length + $index;
                }

                // Check bounds
                if ($index < 0 || $index >= $length) {
                    throw new RuntimeException("Array index out of bounds: {$property->value}");
                }

                $value = $object->value[$index];
                if ($object instanceof StringValue) {
                    $value = new StringValue($object->value[$index]);
                }
            } else if ($property instanceof StringValue) {
                $value = $object->builtins[$property->value];
            } else {
                throw new RuntimeException("Cannot access property with non-string/non-number: got {$property->type}");
            }
        } else {
            if (!($property instanceof StringValue)) {
                throw new RuntimeException("Cannot access property with non-string: got {$property->type}");
            }
            $value = $object->builtins[$property->value];
        }

        return $value instanceof RuntimeValue ? $value : new UndefinedValue();
    }

    private function evaluateSet(SetStatement $node, Environment $environment): NullValue
    {
        $rhs = $this->evaluate($node->value, $environment);
        if ($node->assignee->type === "Identifier") {
            $environment->setVariable($node->assignee->value, $rhs);
        } elseif ($node->assignee->type === "MemberExpression") {
            $object = $this->evaluate($node->assignee->object, $environment);
            if (!($object instanceof ObjectValue)) {
                throw new RuntimeException("Cannot assign to member of non-object");
            }
            $object->value[$node->assignee->property->value] = $rhs;
        } else {
            throw new RuntimeException("Invalid LHS in assignment");
        }

        return new NullValue();
    }

    private function evaluateIf(IfStatement $node, Environment $environment): StringValue
    {
        $test = $this->evaluate($node->test, $environment);
        if ($test->evaluateAsBool()->value) {
            return $this->evaluateBlock($node->body, $environment);
        } else {
            return $this->evaluateBlock($node->alternate, $environment);
        }
    }

    private function evaluateFor(ForStatement $node, Environment $environment): StringValue
    {
        $scope = new Environment($environment);

        $iterable = $test = null;
        if ($node->iterable instanceof SelectExpression) {
            $iterable = $this->evaluate($node->iterable->lhs, $scope);
            $test = $node->iterable->test;
        } else {
            $iterable = $this->evaluate($node->iterable, $scope);
        }

        if (!($iterable instanceof ArrayValue)) {
            throw new RuntimeException("Expected iterable type in for loop: got $iterable->type");
        }

        $items = [];
        $scopeUpdateFunctions = [];

        foreach ($iterable->value as $i => $current) {
            $loopScope = new Environment($scope);

            $current = $iterable->value[$i];

            $scopeUpdateFunction = null;

            if ($node->loopvar instanceof Identifier) {
                $scopeUpdateFunction = fn($scope) => $scope->setVariable($node->loopvar->value, $current);
            } elseif ($node->loopvar instanceof TupleLiteral) {
                if (!($current instanceof ArrayValue)) {
                    throw new RuntimeException("Cannot unpack non-iterable type");
                }

                $loopVarLength = count($node->loopvar->value);
                $currentLength = count($current->value);
                if ($loopVarLength !== $currentLength) {
                    throw new RuntimeException(sprintf("Too %s items to unpack", $loopVarLength > $currentLength ? "few" : "many"));
                }

                $scopeUpdateFunction = function ($scope) use ($node, $current) {
                    foreach ($node->loopvar->value as $j => $identifier) {
                        if (!($identifier instanceof Identifier)) {
                            throw new RuntimeException("Cannot unpack non-identifier type: {$identifier->type}");
                        }
                        $scope->setVariable($identifier->value, $current->value[$j]);
                    }
                };
            } else {
                throw new RuntimeException("Invalid loop variable type: {$node->loopvar->type}");
            }

            if ($test !== null) {
                $scopeUpdateFunction($loopScope);

                $testValue = $this->evaluate($test, $loopScope);
                if (!$testValue->evaluateAsBool()->value) {
                    continue;
                }
            }

            $items[] = $current;
            $scopeUpdateFunctions[] = $scopeUpdateFunction;
        }

        $result = "";
        $noIteration = true;
        $length = count($items);

        for ($i = 0; $i < $length; ++$i) {
            $loop = [
                "index" => new NumericValue($i + 1),
                "index0" => new NumericValue($i),
                "revindex" => new NumericValue($length - $i),
                "revindex0" => new NumericValue($length - $i - 1),
                "first" => new BooleanValue($i === 0),
                "last" => new BooleanValue($i === $length - 1),
                "length" => new NumericValue($length),
                "previtem" => $i > 0 ? $items[$i - 1] : new UndefinedValue(),
                "nextitem" => $i < $length - 1 ? $items[$i + 1] : new UndefinedValue(),
            ];

            $scope->setVariable("loop", new ObjectValue($loop));

            $scopeUpdateFunction = $scopeUpdateFunctions[$i];
            $scopeUpdateFunction($scope);
            $evaluated = $this->evaluateBlock($node->body, $scope);
            $result .= $evaluated->value;

            $noIteration = false; // At least one iteration took place
        }

        if ($noIteration) {
            $defaultEvaluated = $this->evaluateBlock($node->defaultBlock, $scope);
            $result .= $defaultEvaluated->value;
        }

        return new StringValue($result);
    }

    private function evaluateMacro(Macro $node, Environment $environment): NullValue
    {
        $environment->setVariable($node->name->value, new FunctionValue(function ($args, $scope) use ($node, $environment) {
            $macroScope = new Environment($scope);
            $args = array_slice($args, 0, -1);

            $kwargs = $args[count($args) - 1];

            if (!($kwargs instanceof KeywordArgumentsValue)) {
                $kwargs = null;
            }

            for ($i = 0; $i < count($node->args); ++$i) {
                $nodeArg = $node->args[$i];
                $passedArg = $args[$i];

                if ($nodeArg instanceof Identifier) {
                    if (!$passedArg) {
                        throw new RuntimeException("Missing positional argument: {$nodeArg->value}");
                    }

                    $macroScope->setVariable($nodeArg->value, $passedArg);
                } elseif ($nodeArg instanceof KeywordArgumentExpression) {
                    $value = $passedArg
                        ?? $kwargs->value[$nodeArg->key->value]
                        ?? $this->evaluate($nodeArg->value, $macroScope);
                    $macroScope->setVariable($nodeArg->key->value, $value);
                } else {
                    throw new RuntimeException("Unknown argument type: {$nodeArg->type}");
                }
            }

            return $this->evaluateBlock($node->body, $macroScope);
        }));

        // Macros are not evaluated immediately, so we return null
        return new NullValue();
    }
}
