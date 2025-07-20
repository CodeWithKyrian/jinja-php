<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\Core;

use Codewithkyrian\Jinja\AST\ArrayLiteral;
use Codewithkyrian\Jinja\AST\BinaryExpression;
use Codewithkyrian\Jinja\AST\BreakStatement;
use Codewithkyrian\Jinja\AST\CallExpression;
use Codewithkyrian\Jinja\AST\CallStatement;
use Codewithkyrian\Jinja\AST\Comment;
use Codewithkyrian\Jinja\AST\FilterExpression;
use Codewithkyrian\Jinja\AST\ForStatement;
use Codewithkyrian\Jinja\AST\Identifier;
use Codewithkyrian\Jinja\AST\IfStatement;
use Codewithkyrian\Jinja\AST\KeywordArgumentExpression;
use Codewithkyrian\Jinja\AST\Macro;
use Codewithkyrian\Jinja\AST\MemberExpression;
use Codewithkyrian\Jinja\AST\ObjectLiteral;
use Codewithkyrian\Jinja\AST\Program;
use Codewithkyrian\Jinja\AST\SelectExpression;
use Codewithkyrian\Jinja\AST\ContinueStatement;
use Codewithkyrian\Jinja\AST\FilterStatement;
use Codewithkyrian\Jinja\AST\FloatLiteral;
use Codewithkyrian\Jinja\AST\IntegerLiteral;
use Codewithkyrian\Jinja\AST\SetStatement;
use Codewithkyrian\Jinja\AST\SliceExpression;
use Codewithkyrian\Jinja\AST\SpreadExpression;
use Codewithkyrian\Jinja\AST\Statement;
use Codewithkyrian\Jinja\AST\StringLiteral;
use Codewithkyrian\Jinja\AST\TernaryExpression;
use Codewithkyrian\Jinja\AST\TestExpression;
use Codewithkyrian\Jinja\AST\TupleLiteral;
use Codewithkyrian\Jinja\AST\UnaryExpression;
use Codewithkyrian\Jinja\Exceptions\ParserException;
use Codewithkyrian\Jinja\Exceptions\SyntaxError;

use function Codewithkyrian\Jinja\{array_every, array_some};

/**
 * Generate the Abstract Syntax Tree (AST) from a list of tokens.
 */
class Parser
{
    /** @var Token[] */
    private array $tokens;
    private int $current = 0;
    private Program $program;

    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
        $this->program = new Program([]);
    }

    public static function make(array $tokens): static
    {
        return new self($tokens);
    }

    /**
     *  Consume the next token if it matches the expected type, otherwise throw an error.
     * @param TokenType $type The expected token type
     * @param string $error The error message to throw if the token does not match the expected type
     * @return Token|mixed
     */
    private function expect(TokenType $type, string $error)
    {
        if ($this->current >= count($this->tokens)) {
            throw new ParserException("Parser Error: {$error}. Unexpected end of input.");
        }

        $token = $this->tokens[$this->current++];

        if ($token->type !== $type) {
            throw new ParserException("Parser Error: {$error}. {$token->type->value} !== {$type->value}.");
        }

        return $token;
    }

    private function expectIdentifier(string $name): void
    {
        if (!$this->isIdentifier($name)) {
            $currentToken = $this->tokens[$this->current] ?? null;
            $value = $currentToken?->value ?? "end of input";
            throw new SyntaxError("Expected identifier {$name}, got {$value} instead");
        }
        $this->current++;
    }

    /**
     * Generate the AST from the tokens.
     */
    public function parse(): Program
    {
        while ($this->current < count($this->tokens)) {
            $this->program->body[] = $this->parseAny();
        }
        return $this->program;
    }

    private function parseAny()
    {
        if ($this->current >= count($this->tokens)) {
            throw new ParserException("Unexpected end of input");
        }

        $token = $this->tokens[$this->current];

        return match ($token->type) {
            TokenType::Comment => new Comment($this->tokens[$this->current++]->value),
            TokenType::Text => $this->parseText(),
            TokenType::OpenStatement => $this->parseJinjaStatement(),
            TokenType::OpenExpression => $this->parseJinjaExpression(),
            default => throw new ParserException("Unexpected token type: {$token->type}"),
        };
    }

    private function not(...$types): bool
    {
        if ($this->current + count($types) > count($this->tokens)) {
            return true; // If we're past the end, we're "not" any of these types
        }
        return array_some($types, fn($type, $i) => $type !== $this->tokens[$this->current + $i]->type);
    }

    private function is(...$types): bool
    {
        if ($this->current + count($types) > count($this->tokens)) {
            return false; // If we're past the end, we can't match any types
        }
        return array_every($types, fn($type, $i) => $type === $this->tokens[$this->current + $i]->type);
    }

    private function isIdentifier(string ...$names): bool
    {
        return (
            $this->current + count($names)  <= count($this->tokens) &&
            array_every($names, fn($name, $i) => $this->tokens[$this->current + $i]->type === TokenType::Identifier && $this->tokens[$this->current + $i]->value === $name)
        );
    }

    private function isStatement(string ...$names): bool
    {
        return (
            isset($this->tokens[$this->current], $this->tokens[$this->current + 1]) &&
            $this->tokens[$this->current]->type === TokenType::OpenStatement &&
            $this->tokens[$this->current + 1]->type === TokenType::Identifier &&
            in_array($this->tokens[$this->current + 1]->value, $names)
        );
    }

    private function parseText(): StringLiteral
    {
        return new StringLiteral($this->expect(TokenType::Text, "Expected text token")->value);
    }

    /**
     * @throws SyntaxError
     */
    private function parseJinjaStatement(): Statement
    {
        // Consume {% token
        $this->expect(TokenType::OpenStatement, "Expected opening statement token");

        $token = $this->tokens[$this->current];

        if ($token->type !== TokenType::Identifier) {
            throw new SyntaxError("Unknown statement type: {$token->type->value}");
        }

        switch ($token->value) {
            case "set":
                $this->current++;
                $result = $this->parseSetStatement();
                break;

            case "if":
                $this->current++;
                $result = $this->parseIfStatement();

                $this->expect(TokenType::OpenStatement, "Expected {% token");
                $this->expectIdentifier("endif");
                $this->expect(TokenType::CloseStatement, "Expected %} token");
                break;

            case "macro":
                $this->current++;
                $result = $this->parseMacroStatement();

                $this->expect(TokenType::OpenStatement, "Expected {% token");
                $this->expectIdentifier("endmacro");
                $this->expect(TokenType::CloseStatement, "Expected %} token");
                break;

            case "for":
                $this->current++;
                $result = $this->parseForStatement();

                $this->expect(TokenType::OpenStatement, "Expected {% token");
                $this->expectIdentifier("endfor");
                $this->expect(TokenType::CloseStatement, "Expected %} token");

                break;

            case "call":
                $this->current++;
                $result = $this->parseCallStatement();

                $this->expect(TokenType::OpenStatement, "Expected {% token");
                $this->expectIdentifier("endcall");
                $this->expect(TokenType::CloseStatement, "Expected %} token");

                break;

            case "break":
                $this->current++;
                $this->expect(TokenType::CloseStatement, "Expected closing statement token");
                $result = new BreakStatement();
                break;

            case "continue":
                $this->current++;
                $this->expect(TokenType::CloseStatement, "Expected closing statement token");
                $result = new ContinueStatement();
                break;

            case "filter":
                $this->current++;
                $result = $this->parseFilterStatement();

                $this->expect(TokenType::OpenStatement, "Expected {% token");
                $this->expectIdentifier("endfilter");
                $this->expect(TokenType::CloseStatement, "Expected %} token");

                break;

            default:
                throw new SyntaxError("Unknown statement type: {$token->value}");
        }

        return $result;
    }

    private function parseJinjaExpression(): Statement
    {
        // Consume {{ }} tokens
        $this->expect(TokenType::OpenExpression, "Expected opening expression token");

        $result = $this->parseExpression();

        $this->expect(TokenType::CloseExpression, "Expected closing expression token");

        return $result;
    }

    private function parseSetStatement(): Statement
    {
        $left = $this->parseExpressionSequence();
        $value = null;
        $body = [];

        if ($this->is(TokenType::Equals)) {
            $this->current++;
            $value = $this->parseExpressionSequence();
        } else {
            $this->expect(TokenType::CloseStatement, "Expected %} token");

            while (!$this->isStatement("endset")) {
                $body[] = $this->parseAny();
            }

            $this->expect(TokenType::OpenStatement, "Expected {% token");
            $this->expectIdentifier("endset");
        }

        $this->expect(TokenType::CloseStatement, "Expected %} token");
        return new SetStatement($left, $value, $body);
    }

    private function parseIfStatement(): IfStatement
    {
        $test = $this->parseExpression();

        $this->expect(TokenType::CloseStatement, "Expected closing statement token");

        $body = [];
        $alternate = [];

        // Keep parsing if body until we reach the first {% elif %} or {% else %} or {% endif %}
        while (!$this->isStatement("elif", "else", "endif")) {
            $body[] = $this->parseAny();
        }

        // Alternate branch: Check for {% elif %} or {% else %}
        if ($this->isStatement("elif")) {
            $this->current++; // consume {%
            $this->current++; // consume elif
            $alternate[] = $this->parseIfStatement();
        } else if ($this->isStatement("else")) {
            $this->current++; // consume {%
            $this->current++; // consume else
            $this->expect(TokenType::CloseStatement, "Expected closing statement token");

            while (!$this->isStatement("endif")) {
                $alternate[] = $this->parseAny();
            }
        }

        return new IfStatement($test, $body, $alternate);
    }

    private function parseMacroStatement(): Macro
    {
        $name = $this->parsePrimaryExpression();
        if (!($name instanceof Identifier)) {
            throw new SyntaxError("Expected identifier following macro statement");
        }

        $args = $this->parseArgs();
        $this->expect(TokenType::CloseStatement, "Expected closing statement token");

        $body = [];

        // Keep going until we hit {% endmacro %}
        while (!$this->isStatement("endmacro")) {
            $body[] = $this->parseAny();
        }

        return new Macro($name, $args, $body);
    }

    private function parseExpressionSequence(bool $primary = false): Statement
    {
        $fn = $primary ? [$this, 'parsePrimaryExpression'] : [$this, 'parseExpression'];
        $expressions = [$fn()];
        $isTuple = $this->is(TokenType::Comma);

        while ($isTuple) {
            $this->current++; // consume comma
            $expressions[] = $fn();
            if (!$this->is(TokenType::Comma)) {
                break;
            }
        }

        return $isTuple ? new TupleLiteral($expressions) : $expressions[0];
    }

    /**
     * @throws SyntaxError
     */
    private function parseForStatement(): ForStatement
    {
        $loopVariable = $this->parseExpressionSequence(true);
        if (!($loopVariable instanceof Identifier || $loopVariable instanceof TupleLiteral)) {
            throw new SyntaxError("Expected identifier/tuple for the loop variable, got {$loopVariable->type} instead");
        }

        if (!$this->isIdentifier("in")) {
            throw new SyntaxError("Expected `in` keyword following loop variable");
        }
        $this->current++; // consume in

        $iterable = $this->parseExpression();

        $this->expect(TokenType::CloseStatement, "Expected closing statement token");

        $body = [];

        // Keep going until we hit {% endfor or {% else %}
        while (!$this->isStatement("endfor", "else")) {
            $body[] = $this->parseAny();
        }

        $alternate = [];

        if ($this->isStatement("else")) {
            $this->current++; // consume {%
            $this->current++; // consume else
            $this->expect(TokenType::CloseStatement, "Expected closing statement token");

            // Keep going until we hit {% endfor %}
            while (!$this->isStatement("endfor")) {
                $alternate[] = $this->parseAny();
            }
        }

        return new ForStatement($loopVariable, $iterable, $body, $alternate);
    }

    private function parseCallStatement(): Statement
    {
        $args = null;

        if ($this->is(TokenType::OpenParen)) {
            $args = $this->parseArgs();
        }

        $callee = $this->parsePrimaryExpression();
        if (!($callee instanceof Identifier)) {
            throw new SyntaxError("Expected identifier following call statement");
        }

        $args = $this->parseArgs();
        $this->expect(TokenType::CloseStatement, "Expected closing statement token");

        $body = [];

        while (!$this->isStatement("endcall")) {
            $body[] = $this->parseAny();
        }

        $expression =  new CallExpression($callee, $args);

        return new CallStatement($expression, $args, $body);
    }

    private function parseFilterStatement(): Statement
    {
        $filter = $this->parsePrimaryExpression();
        if ($filter instanceof Identifier && $this->is(TokenType::OpenParen)) {
            $filter = $this->parseCallExpression($filter);
        }

        $this->expect(TokenType::CloseStatement, "Expected closing statement token");

        $body = [];

        while (!$this->isStatement("endfilter")) {
            $body[] = $this->parseAny();
        }

        return new FilterStatement($filter, $body);
    }

    private function parseExpression(): Statement
    {
        // Choose parse function with the lowest precedence
        return $this->parseIfExpression();
    }

    private function parseIfExpression(): Statement
    {
        $trueExpression = $this->parseLogicalOrExpression();

        if ($this->isIdentifier("if")) {
            $this->current++;
            $test = $this->parseLogicalOrExpression();

            if ($this->isIdentifier("else")) {
                $this->current++; // consume else
                $falseExpression = $this->parseIfExpression();
                return new TernaryExpression($test, $trueExpression, $falseExpression);
            } else {
                return new SelectExpression($trueExpression, $test);
            }
        }
        return $trueExpression;
    }

    private function parseLogicalOrExpression(): Statement
    {
        $left = $this->parseLogicalAndExpression();

        while ($this->isIdentifier("or")) {
            $operator = $this->tokens[$this->current];
            $this->current++;
            $right = $this->parseLogicalAndExpression();
            $left = new BinaryExpression($operator, $left, $right);
        }

        return $left;
    }

    private function parseLogicalAndExpression(): Statement
    {
        $left = $this->parseLogicalNegationExpression();
        while ($this->isIdentifier("and")) {
            $operator = $this->tokens[$this->current];
            $this->current++;
            $right = $this->parseLogicalNegationExpression();
            $left = new BinaryExpression($operator, $left, $right);
        }
        return $left;
    }

    private function parseLogicalNegationExpression(): Statement
    {
        $right = null;
        while ($this->isIdentifier("not")) {
            $operator = $this->tokens[$this->current];
            $this->current++;
            $arg = $this->parseLogicalNegationExpression();
            $right = new UnaryExpression($operator, $arg);
        }
        return $right ?: $this->parseComparisonExpression();
    }

    private function parseComparisonExpression(): Statement
    {
        $left = $this->parseAdditiveExpression();

        while (true) {
            if ($this->isIdentifier("not", "in")) {
                $operator = new Token("not in", TokenType::Identifier);
                $this->current += 2;
            } elseif ($this->isIdentifier("in")) {
                $operator = $this->tokens[$this->current++];
            } elseif ($this->is(TokenType::ComparisonBinaryOperator)) {
                $operator = $this->tokens[$this->current++];
            } else {
                break;
            }

            $right = $this->parseAdditiveExpression();
            $left = new BinaryExpression($operator, $left, $right);
        }

        return $left;
    }

    private function parseAdditiveExpression(): Statement
    {
        $left = $this->parseMultiplicativeExpression();
        while ($this->is(TokenType::AdditiveBinaryOperator)) {
            $operator = $this->tokens[$this->current];
            $this->current++;
            $right = $this->parseMultiplicativeExpression();
            $left = new BinaryExpression($operator, $left, $right);
        }
        return $left;
    }

    private function parseCallMemberExpression(): Statement
    {
        $member = $this->parseMemberExpression($this->parsePrimaryExpression());

        if ($this->is(TokenType::OpenParen)) {
            return $this->parseCallExpression($member);
        }

        return $member;
    }

    private function parseCallExpression(Statement $callee): CallExpression
    {
        $expression = new CallExpression($callee, $this->parseArgs());

        $expression = $this->parseMemberExpression($expression); // foo->x()->y

        if ($this->is(TokenType::OpenParen)) {
            $expression = $this->parseCallExpression($expression); // foo->x()()
        }

        return $expression;
    }

    /**
     * @return Statement[]
     */
    private function parseArgs(): array
    {
        $this->expect(TokenType::OpenParen, "Expected opening parenthesis for arguments list");
        $args = $this->parseArgumentsList();
        $this->expect(TokenType::CloseParen, "Expected closing parenthesis for arguments list");
        return $args;
    }

    /**
     * @return Statement[]
     * @throws SyntaxError
     */
    private function parseArgumentsList(): array
    {
        $arguments = [];

        while (!$this->is(TokenType::CloseParen)) {
            // unpacking *expr
            if (
                $this->tokens[$this->current]->type === TokenType::MultiplicativeBinaryOperator
                && $this->tokens[$this->current]->value === "*"
            ) {
                $this->current++; // consume asterisk
                $expression = $this->parseExpression();
                $argument = new SpreadExpression($expression);
            } else {
                $argument = $this->parseExpression();

                if ($this->is(TokenType::Equals)) {
                    // keyword argument
                    // e.g., func(x = 5, y = a or b)
                    $this->current++; // consume equals
                    if (!($argument instanceof Identifier)) {
                        throw new SyntaxError("Expected identifier for keyword argument");
                    }
                    $value = $this->parseExpression();
                    $argument = new KeywordArgumentExpression($argument, $value);
                }
            }

            $arguments[] = $argument;
            if ($this->is(TokenType::Comma)) {
                $this->current++; // consume comma
            }
        }

        return $arguments;
    }

    private function parseMemberExpressionArgumentsList(): Statement
    {
        $slices = [];
        $isSlice = false;
        while (!$this->is(TokenType::CloseSquareBracket)) {
            if ($this->is(TokenType::Colon)) {
                $slices[] = null;
                $this->current++; // consume colon
                $isSlice = true;
            } else {
                $slices[] = $this->parseExpression();
                if ($this->is(TokenType::Colon)) {
                    $this->current++; // consume colon
                    $isSlice = true;
                }
            }
        }
        if (empty($slices)) {
            throw new SyntaxError("Expected at least one argument for member/slice expression");
        }

        if ($isSlice) {
            if (count($slices) > 3) {
                throw new SyntaxError("Expected 0-3 arguments for slice expression");
            }

            return new SliceExpression(...$slices);
        }

        return $slices[0];
    }

    private function parseMemberExpression(Statement $object): Statement
    {

        while ($this->is(TokenType::Dot) || $this->is(TokenType::OpenSquareBracket)) {
            $operator = $this->tokens[$this->current]; // . or [
            $this->current++; // assume operator token is consumed
            $computed = $operator->type === TokenType::OpenSquareBracket;

            if ($computed) {
                // computed (i.e., bracket notation: obj[expr])
                $property = $this->parseMemberExpressionArgumentsList();
                $this->expect(TokenType::CloseSquareBracket, "Expected closing square bracket");
            } else {
                // non-computed (i.e., dot notation: obj.expr)
                $property = $this->parsePrimaryExpression();
                if ($property->type !== "Identifier") {
                    throw new SyntaxError("Expected identifier following dot operator");
                }
            }
            $object = new MemberExpression($object, $property, $computed);
        }
        return $object;
    }

    private function parseMultiplicativeExpression(): Statement
    {
        $left = $this->parseTestExpression();
        while ($this->is(TokenType::MultiplicativeBinaryOperator)) {
            $operator = $this->tokens[$this->current];
            $this->current++;
            $right = $this->parseTestExpression();
            $left = new BinaryExpression($operator, $left, $right);
        }
        return $left;
    }

    private function parseTestExpression(): Statement
    {
        $operand = $this->parseFilterExpression();

        while ($this->isIdentifier('is')) {
            $this->current++; // consume is
            $negate = $this->isIdentifier("not");

            if ($negate) {
                $this->current++; // consume not
            }

            $filter = $this->parsePrimaryExpression();

            if (!($filter instanceof Identifier)) {
                throw new SyntaxError("Expected identifier for the test");
            }

            $operand = new TestExpression($operand, $negate, $filter);
        }
        return $operand;
    }

    private function parseFilterExpression(): Statement
    {
        $operand = $this->parseCallMemberExpression();
        while ($this->is(TokenType::Pipe)) {
            $this->current++; // consume pipe
            $filter = $this->parsePrimaryExpression();
            if (!($filter instanceof Identifier)) {
                throw new SyntaxError("Expected identifier for the filter");
            }
            if ($this->is(TokenType::OpenParen)) {
                $filter = $this->parseCallExpression($filter);
            }
            $operand = new FilterExpression($operand, $filter);
        }
        return $operand;
    }

    private function parsePrimaryExpression(): Statement
    {
        if ($this->current >= count($this->tokens)) {
            throw new SyntaxError("Unexpected end of input");
        }

        $token = $this->tokens[$this->current++];
        switch ($token->type) {
            case TokenType::NumericLiteral:
                $value = $token->value;

                return str_contains($value, ".")
                    ? new FloatLiteral(floatval($value))
                    : new IntegerLiteral(intval($value));

            case TokenType::StringLiteral:
                $value = $token->value;
                while ($this->is(TokenType::StringLiteral)) {
                    $value .= $this->tokens[$this->current++]->value;
                }

                return new StringLiteral($value);

            case TokenType::Identifier:
                return new Identifier($token->value);

            case TokenType::OpenParen:
                $expression = $this->parseExpressionSequence();
                $this->expect(TokenType::CloseParen, "Expected closing parenthesis, got {$this->tokens[$this->current]->type->value} instead");
                return $expression;

            case TokenType::OpenSquareBracket:
                $values = [];

                while (!$this->is(TokenType::CloseSquareBracket)) {
                    $values[] = $this->parseExpression();
                    if ($this->is(TokenType::Comma)) {
                        $this->current++; // consume comma
                    }
                }
                $this->current++; // consume closing square bracket
                return new ArrayLiteral($values);

            case TokenType::OpenCurlyBracket:
                $values = [];
                while (!$this->is(TokenType::CloseCurlyBracket)) {
                    $key = $this->parseExpression();
                    $this->expect(TokenType::Colon, "Expected colon between key and value in object literal");
                    $value = $this->parseExpression();
                    $values[] = ['key' => $key, 'value' => $value]; // TODO: Use SPLObjectStorage
                    if ($this->is(TokenType::Comma)) {
                        $this->current++; // consume comma
                    }
                }
                $this->current++; // consume closing curly bracket
                return new ObjectLiteral($values);

            default:
                throw new SyntaxError("Unexpected token: {$token->type}");
        }
    }
}
