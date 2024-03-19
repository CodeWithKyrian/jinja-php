<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Core;

use Codewithkyrian\Jinja\AST\BinaryExpression;
use Codewithkyrian\Jinja\AST\CallExpression;
use Codewithkyrian\Jinja\AST\FilterExpression;
use Codewithkyrian\Jinja\AST\ForStatement;
use Codewithkyrian\Jinja\AST\Identifier;
use Codewithkyrian\Jinja\AST\IfStatement;
use Codewithkyrian\Jinja\AST\MemberExpression;
use Codewithkyrian\Jinja\AST\Program;
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

    public function __construct(Environment $env = null)
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

            case "NumericLiteral":
                return new NumericValue($statement->value);

            case "StringLiteral":
                return new StringValue($statement->value);

            case "BooleanLiteral":
                return new BooleanValue($statement->value);

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
     * Evaluates expressions following the filter operation type.
     * @throws SyntaxError
     * @throws \Exception
     */
    private function evaluateFilterExpression(FilterExpression $node, Environment $environment): RuntimeValue
    {
        $operand = $this->evaluate($node->operand, $environment);

        if ($node->filter instanceof Identifier) {
            // ArrayValue filters
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

            $filterName = $filter->callee->value;

            if ($operand instanceof ArrayValue) {
                switch ($filterName) {
                    case "selectattr":
                        // Check if all items in the array are instances of ObjectValue
                        if (array_some($operand->value, fn($x) => !($x instanceof ObjectValue))) {
                            throw new \Exception("`selectattr` can only be applied to an array of objects");
                        }

                        if (array_some($filter->args, fn($x) => $x->type !== "StringLiteral")) {
                            throw new \Exception("The arguments of `selectattr` must be strings");
                        }


                        [$attrExpr, $testNameExpr, $valueExpr] = $filter->args;

                        $attr = $this->evaluate($attrExpr, $environment);
                        $testName = isset($testNameExpr) ? $this->evaluate($testNameExpr, $environment) : null;
                        $value = isset($valueExpr) ? $this->evaluate($valueExpr, $environment) : null;

                        if (!($attr instanceof StringValue)) {
                            throw new \Exception("The attribute name of `selectattr` must be a string");
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
                            $a = $item->value[$attr->value] ?? null;
                            if ($a !== null) {
                                if ($testFunction($a, $value)) {
                                    $filtered[] = $item;
                                }
                            }
                        }

                        return new ArrayValue($filtered);
                    default:
                        throw new \Exception("Unknown ArrayValue filter: $filterName");
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
        $args = [];
        $kwargs = [];
        foreach ($expr->args as $argument) {
            if ($argument->type === "KeywordArgumentExpression") {
                $kwarg = $this->evaluate($argument->value, $environment);
                $kwargs[$argument->key->value] = $kwarg;
            } else {
                $args[] = $this->evaluate($argument, $environment);
            }
        }
        if (!empty($kwargs)) {
            $args[] = new ObjectValue($kwargs);
        }

        /** @var FunctionValue $fn */
        $fn = $this->evaluate($expr->callee, $environment);
        if ($fn->type !== "FunctionValue") {
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

        // Assuming ObjectValue wraps an associative array
        if ($object instanceof ObjectValue) {
            if (!($property instanceof StringValue)) {
                throw new RuntimeException("Cannot access property with non-string: got {$property->type}");
            }
            $value = $object->value[$property->value] ?? $object->builtins[$property->value];
        } else if ($object instanceof ArrayValue || $object instanceof StringValue) {
            if ($property instanceof NumericValue) {
                $value = $object->value[$property->value];
                if ($object instanceof StringValue) {
                    $value = new StringValue($object->value[$property->value]);
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
        // Create a new scope for the loop
        $scope = new Environment($environment);

        $iterable = $this->evaluate($node->iterable, $scope);
        if (!($iterable instanceof ArrayValue)) {
            throw new RuntimeException("Expected iterable type in for loop: got $iterable->type");
        }

        $result = "";

        foreach ($iterable->value as $i => $current) {
            // Construct the loop variable
            $length = count($iterable->value);
            $loop = [
                "index" => new NumericValue($i + 1),
                "index0" => new NumericValue($i),
                "revindex" => new NumericValue($length - $i),
                "revindex0" => new NumericValue($length - $i - 1),
                "first" => new BooleanValue($i === 0),
                "last" => new BooleanValue($i === $length - 1),
                "length" => new NumericValue($length),
                "previtem" => $i > 0 ? $iterable->value[$i - 1] : new UndefinedValue(),
                "nextitem" => $i < $length - 1 ? $iterable->value[$i + 1] : new UndefinedValue(),
            ];
            $scope->setVariable("loop", new ObjectValue($loop));

            // Set the loop variable for this iteration
            if ($node->loopvar instanceof Identifier) {
                $scope->setVariable($node->loopvar->value, $current);
            } elseif ($node->loopvar instanceof TupleLiteral) {
                if (!($current instanceof ArrayValue)) {
                    throw new RuntimeException("Cannot unpack non-iterable type");
                }

                $loopVarLength = count($node->loopvar->value);
                $currentLength = count($current->value);
                if ($loopVarLength !== $currentLength) {
                    throw new RuntimeException(sprintf("Too %s items to unpack", $loopVarLength > $currentLength ? "few" : "many"));
                }

                foreach ($node->loopvar->value as $j => $identifier) {
                    if (!($identifier instanceof Identifier)) {
                        throw new RuntimeException("Cannot unpack non-identifier type: {$identifier->type}");
                    }
                    $scope->setVariable($identifier->value, $current->value[$j]);
                }
            }

            // Evaluate the body of the loop
            $evaluated = $this->evaluateBlock($node->body, $scope);
            $result .= $evaluated->value;
        }

        return new StringValue($result);
    }


}