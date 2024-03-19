<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\Core;

use Codewithkyrian\Jinja\AST\ArrayLiteral;
use Codewithkyrian\Jinja\AST\BinaryExpression;
use Codewithkyrian\Jinja\AST\BooleanLiteral;
use Codewithkyrian\Jinja\AST\CallExpression;
use Codewithkyrian\Jinja\AST\FilterExpression;
use Codewithkyrian\Jinja\AST\ForStatement;
use Codewithkyrian\Jinja\AST\Identifier;
use Codewithkyrian\Jinja\AST\IfStatement;
use Codewithkyrian\Jinja\AST\KeywordArgumentExpression;
use Codewithkyrian\Jinja\AST\MemberExpression;
use Codewithkyrian\Jinja\AST\NumericLiteral;
use Codewithkyrian\Jinja\AST\ObjectLiteral;
use Codewithkyrian\Jinja\AST\Program;
use Codewithkyrian\Jinja\AST\SetStatement;
use Codewithkyrian\Jinja\AST\SliceExpression;
use Codewithkyrian\Jinja\AST\Statement;
use Codewithkyrian\Jinja\AST\StringLiteral;
use Codewithkyrian\Jinja\AST\TestExpression;
use Codewithkyrian\Jinja\AST\TupleLiteral;
use Codewithkyrian\Jinja\AST\UnaryExpression;
use Codewithkyrian\Jinja\Exceptions\ParserException;
use Codewithkyrian\Jinja\Exceptions\SyntaxError;
use function Codewithkyrian\Jinja\array_every;
use function Codewithkyrian\Jinja\array_some;

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
        $token = $this->tokens[$this->current++];

        if ($token->type !== $type) {
            throw new ParserException("Parser Error: {$error}. {$token->type->value} !== {$type->value}.");
        }

        return $token;
    }


    public function parse(): Program
    {
        while ($this->current < count($this->tokens)) {

            $this->program->body[] = $this->parseAny();
        }
        return $this->program;
    }

    private function parseAny()
    {
        $token = $this->tokens[$this->current];

        return match ($token->type) {
            TokenType::Text => $this->parseText(),
            TokenType::OpenStatement => $this->parseJinjaStatement(),
            TokenType::OpenExpression => $this->parseJinjaExpression(),
            default => throw new ParserException("Unexpected token type: {$token->type}"),
        };
    }



    private function not(...$types): bool
    {
        return $this->current + count($types) <= count($this->tokens)
            && array_some($types, fn($type, $i) => $type !== $this->tokens[$this->current + $i]->type);
    }

    private function is(...$types): bool
    {
        return $this->current + count($types) <= count($this->tokens)
            && array_every($types, fn($type, $i) => $type === $this->tokens[$this->current + $i]->type);
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
        // Consume {% %} tokens
        $this->expect(TokenType::OpenStatement, "Expected opening statement token");

        $tokenType = $this->tokens[$this->current]->type;


        switch ($tokenType) {
            case TokenType::Set:
                $this->current++;
                $result = $this->parseSetStatement();
                $this->expect(TokenType::CloseStatement, "Expected closing statement token");
                break;

            case  TokenType::If:
                $this->current++;
                $result = $this->parseIfStatement();
                $this->expect(TokenType::OpenStatement, "Expected {% token");
                $this->expect(TokenType::EndIf, "Expected endif token");
                $this->expect(TokenType::CloseStatement, "Expected %} token");
                break;

            case TokenType::For:
                $this->current++;
                $result = $this->parseForStatement();

                $this->expect(TokenType::OpenStatement, "Expected {% token");
                $this->expect(TokenType::EndFor, "Expected endfor token");
                $this->expect(TokenType::CloseStatement, "Expected %} token");

                break;

            default:
                throw new SyntaxError("Unknown statement type: $tokenType->value");
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
        $left = $this->parseExpression();

        if ($this->is(TokenType::Equals)) {
            $this->current++;
            $value = $this->parseSetStatement();

            return new SetStatement($left, $value);
        }
        return $left;
    }

    private function parseIfStatement(): IfStatement
    {
        $test = $this->parseExpression();

        $this->expect(TokenType::CloseStatement, "Expected closing statement token");

        $body = [];
        $alternate = [];

        // Keep parsing if body until we reach the first {% elif %} or {% else %} or {% endif %}
        while (!(
            isset($this->tokens[$this->current]) &&
            $this->tokens[$this->current]->type === TokenType::OpenStatement &&
            in_array($this->tokens[$this->current + 1]->type, [TokenType::ElseIf, TokenType::Else, TokenType::EndIf])
        )) {
            $body[] = $this->parseAny();
        }

        // Alternate branch: Check for {% elif %} or {% else %}
        if (
            isset($this->tokens[$this->current]) &&
            $this->tokens[$this->current]->type === TokenType::OpenStatement &&
            $this->tokens[$this->current + 1]->type !== TokenType::EndIf
        ) {
            $this->current++; // consume {% token
            if ($this->is(TokenType::ElseIf)) {
                $this->expect(TokenType::ElseIf, "Expected elseif token");
                $alternate[] = $this->parseIfStatement();
            } else {
                $this->expect(TokenType::Else, "Expected else token");
                $this->expect(TokenType::CloseStatement, "Expected closing statement token");

                // keep going until we hit {% endif %}
                while (!(
                    isset($this->tokens[$this->current]) &&
                    $this->tokens[$this->current]->type === TokenType::OpenStatement &&
                    $this->tokens[$this->current + 1]->type === TokenType::EndIf
                )) {
                    $alternate[] = $this->parseAny();
                }
            }
        }

        return new IfStatement($test, $body, $alternate);
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
        // e.g., `message` in `for message in messages`
        $loopVariable = $this->parseExpressionSequence(true); // should be an identifier
        if (!($loopVariable instanceof Identifier || $loopVariable instanceof TupleLiteral)) {
            throw new SyntaxError("Expected identifier/tuple for the loop variable, got {$loopVariable->type} instead");
        }

        $this->expect(TokenType::In, "Expected `in` keyword following loop variable");

        $iterable = $this->parseExpression();

        $this->expect(TokenType::CloseStatement, "Expected closing statement token");

        $body = [];

        // Keep going until we hit {% endfor
        while ($this->not(TokenType::OpenStatement, TokenType::EndFor)) {
            $body[] = $this->parseAny();
        }

        return new ForStatement($loopVariable, $iterable, $body);
    }

    private function parseExpression(): Statement
    {
        // Choose parse function with the lowest precedence
        return $this->parseTernaryExpression();
    }

    private function parseTernaryExpression(): Statement
    {
        $a = $this->parseLogicalOrExpression();

        if ($this->is(TokenType::If)) {
            $this->current++;
            $predicate = $this->parseLogicalOrExpression();
            $this->expect(TokenType::Else, "Expected else token");
            $b = $this->parseLogicalOrExpression();
            return new IfStatement($predicate, [$a], [$b]);
        }
        return $a;
    }

    private function parseLogicalOrExpression(): Statement
    {
        $left = $this->parseLogicalAndExpression();

        while ($this->is(TokenType::Or)) {
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
        while ($this->is(TokenType::And)) {
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
        while ($this->is(TokenType::Not)) {
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
        while ($this->is(TokenType::ComparisonBinaryOperator)
            || $this->is(TokenType::In)
            || $this->is(TokenType::NotIn)
        ) {
            $operator = $this->tokens[$this->current];
            $this->current++;
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
        $member = $this->parseMemberExpression();
        if ($this->is(TokenType::OpenParen)) {
            return $this->parseCallExpression($member);
        }
        return $member;
    }

    private function parseCallExpression($callee): CallExpression
    {
        $callExpression = new CallExpression($callee, $this->parseArgs());
        if ($this->is(TokenType::OpenParen)) {
            $callExpression = $this->parseCallExpression($callExpression);
        }
        return $callExpression;
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
        $args = [];
        while (!$this->is(TokenType::CloseParen)) {
            $argument = $this->parseExpression();

            if ($this->is(TokenType::Equals)) {
                $this->current++; // consume equals
                if (!($argument instanceof Identifier)) {
                    throw new SyntaxError("Expected identifier for keyword argument");
                }
                $value = $this->parseExpression();
                $argument = new KeywordArgumentExpression($argument, $value);
            }
            $args[] = $argument;
            if ($this->is(TokenType::Comma)) {
                $this->current++; // consume comma
            }
        }
        return $args;
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

    private function parseMemberExpression(): Statement
    {
        $object = $this->parsePrimaryExpression();

        while ($this->is(TokenType::Dot) || $this->is(TokenType::OpenSquareBracket)) {
            $operator = $this->tokens[$this->current]; // . or [
            $this->current++; // assume operator token is consumed
            $computed = $operator->type !== TokenType::Dot;

            if ($computed) {
                // computed (i.e., bracket notation: obj[expr])
                $property = $this->parseMemberExpressionArgumentsList();
                $this->expect(TokenType::CloseSquareBracket, "Expected closing square bracket");
            }
            else{
                // non-computed (i.e., dot notation: obj.expr)
                $property = $this->parsePrimaryExpression();
                if($property->type !== "Identifier")
                {
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
        while ($this->is(TokenType::Is)) {
            $this->current++; // consume is
            $negate = $this->is(TokenType::Not);
            if ($negate) {
                $this->current++; // consume not
            }
            $filter = $this->parsePrimaryExpression();
            if ($filter instanceof BooleanLiteral) {
                // PHP version: treating boolean literals as identifiers might require manual handling
                $filter = new Identifier((string)$filter->value);
            }
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
        $token = $this->tokens[$this->current];
        switch ($token->type) {
            case TokenType::NumericLiteral:
                $this->current++;
                return new NumericLiteral(floatval($token->value));

            case TokenType::StringLiteral:
                $this->current++;
                return new StringLiteral($token->value);

            case TokenType::BooleanLiteral:
                $this->current++;
                return new BooleanLiteral($token->value === "true");

            case TokenType::Identifier:
                $this->current++;
                return new Identifier($token->value);

            case TokenType::OpenParen:
                $this->current++; // consume opening parenthesis
                $expression = $this->parseExpressionSequence();
                if ($this->tokens[$this->current]->type !== TokenType::CloseParen) {
                    throw new SyntaxError("Expected closing parenthesis, got {$this->tokens[$this->current]->type} instead");
                }
                $this->current++; // consume closing parenthesis
                return $expression;

            case TokenType::OpenSquareBracket:
                $this->current++; // consume opening square bracket
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
                $this->current++; // consume opening curly bracket
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