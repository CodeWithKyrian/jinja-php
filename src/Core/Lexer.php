<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Core;

use Codewithkyrian\Jinja\Exceptions\SyntaxError;

class Lexer
{
    /**
     * Preprocess a Jinja template string according to Jinja's default whitespace handling.
     *
     * @param string $template The template string to preprocess.
     * @param bool $lstripBlocks Whether to strip whitespace from the beginning of lines.
     * @param bool $trimBlocks Whether to remove the first newline after template tags.
     * @return string The preprocessed template string.
     */
    public static function preprocess(string $template, bool $lstripBlocks = false, bool $trimBlocks = false): string
    {
        // Remove trailing newline if present, as in Jinja's default configuration.
        $template = rtrim($template, "\n");

        // Replace comments with a placeholder to avoid interference.
        $template = preg_replace('/{#.*?#}/s', '{##}', $template);

        // Strip whitespace from block beginnings.
        if ($lstripBlocks) {
            $template = preg_replace('/^[ \t]*({[%#])/', '$1', $template);
        }

        // Remove first newline after template tags
        if ($trimBlocks) {
            $template = preg_replace('/([%#]})\n/', '$1', $template);
        }

        // Process template string further based on options and Jinja standards
        $template = str_replace('{##}', '', $template); // Remove placeholders for comments
        $template = preg_replace('/-%}\s*/', '%}', $template);
        $template = preg_replace('/\s*{%-/', '{%', $template);
        $template = preg_replace('/-}}\s*/', '}}', $template);
        $template = preg_replace('/\s*{{-/', '{{', $template);

        return $template;
    }

    /**
     *  Generate a list of tokens from a source string.
     * @param string $source The source string to be tokenized
     * @param bool $lstripBlocks Whether to strip whitespace from the beginning of lines.
     * @param bool $trimBlocks Whether to remove the first newline after template tags.
     * @return Token[]
     * @throws SyntaxError
     */
    public static function tokenize(string $source, bool $lstripBlocks = false, bool $trimBlocks = false): array
    {
        /** @var Token[] $tokens */
        $tokens = [];

        $src = self::preprocess($source, $lstripBlocks, $trimBlocks); // Assume preprocess is already adapted to PHP

        $cursorPosition = 0;
        $srcLength = strlen($src);

        $isWord = fn($char) => preg_match('/\w/', $char) == 1;

        $isInteger = fn($char) => preg_match('/[0-9]/', $char) == 1;

        $consumeWhile = function (callable $predicate) use (&$src, &$cursorPosition, $srcLength) {
            $str = "";
            while ($predicate($src[$cursorPosition])) {
                // Check for escaped characters
                if ($src[$cursorPosition] === "\\") {
                    // Consume the backslash
                    ++$cursorPosition;
                    // Check for end of input
                    if ($cursorPosition >= strlen($src)) {
                        throw new SyntaxError("Unexpected end of input");
                    }

                    // Add the escaped character
                    $escaped = $src[$cursorPosition++];
                    $unescaped = Token::ESCAPE_CHARACTERS[$escaped] ?? throw new SyntaxError("Unexpected escaped character: $escaped");
                    $str .= $unescaped; // Adjust based on your ESCAPE_CHARACTERS handling
                    continue;
                }

                $str .= $src[$cursorPosition++];
                if ($cursorPosition >= strlen($src)) throw new SyntaxError("Unexpected end of input");
            }
            return $str;
        };


        // Build each token until end of input
        while ($cursorPosition < $srcLength) {
            // First, consume all text that is outside a Jinja statement or expression
            $lastTokenType = end($tokens)->type ?? null;

            if (
                is_null($lastTokenType)
                || $lastTokenType === TokenType::CloseStatement
                || $lastTokenType === TokenType::CloseExpression
            ) {
                $text = "";
                while (
                    $cursorPosition < $srcLength
                    && !($src[$cursorPosition] === "{" && ($src[$cursorPosition + 1] === "%" || $src[$cursorPosition + 1] === "{"))) {
                    $text .= $src[$cursorPosition++];
                }


                if (strlen($text) > 0) {
                    $tokens[] = new Token($text, TokenType::Text);
                    continue;
                }
            }

            // Consume (and ignore) all whitespace inside Jinja statements or expressions
            $consumeWhile(fn($char) => preg_match('/\s/', $char));

//            if ($cursorPosition >= $srcLength) break; // End of input check

            $char = $src[$cursorPosition];


            // Check for unary operators
            if ($char === "-" || $char === "+") {
                $lastTokenType = end($tokens)->type;
                if ($lastTokenType === TokenType::Text || $lastTokenType === null) {
                    throw new SyntaxError("Unexpected character: $char");
                }
                switch ($lastTokenType) {
                    case TokenType::Identifier:
                    case TokenType::NumericLiteral:
                    case TokenType::BooleanLiteral:
                    case TokenType::StringLiteral:
                    case TokenType::CloseParen:
                    case TokenType::CloseSquareBracket:
                        // Part of a binary operator
                        // a - 1, 1 - 1, true - 1, "apple" - 1, (1) - 1, a[1] - 1
                        // Continue parsing normally
                        break;

                    default:
                        // Is part of a unary operator
                        // (-1), [-1], (1 + -1), not -1, -apple
                        ++$cursorPosition; // consume the unary operator

                        // Check for numbers following the unary operator
                        $num = $consumeWhile($isInteger);

                        $tokens[] = new Token(
                            "$char$num",
                            strlen($num) > 0 ? TokenType::NumericLiteral : TokenType::UnaryOperator
                        );
                        continue 2; // Continue the outer loop.
                }
            }

            foreach (Token::ORDERED_MAPPING_TABLE as [$c, $t]) {
                $slice = substr($src, $cursorPosition, strlen($c));

                if ($slice === $c) {
                    $tokens[] = new Token($c, $t);
                    $cursorPosition += strlen($c);
                    continue 2; // Continue the outer loop.
                }
            }

            if ($char === "'" || $char === '"') {
                ++$cursorPosition; // Skip the opening quote
                $str = $consumeWhile(fn($c) => $c !== $char);
                $tokens[] = new Token($str, TokenType::StringLiteral);
                ++$cursorPosition; // Skip the closing quote
                continue;
            }

            if ($isInteger($char)) {
                $num = $consumeWhile($isInteger);
                $tokens[] = new Token($num, TokenType::NumericLiteral);
                continue;
            }

            if ($isWord($char)) {
                $word = $consumeWhile($isWord);

                $type = Token::KEYWORDS[$word] ?? TokenType::Identifier;

                // Special case handling for "not in"
                if ($type === TokenType::In && end($tokens)->type === TokenType::Not) {
                    array_pop($tokens);
                    $tokens[] = new Token("not in", TokenType::NotIn);
                } else {
                    $tokens[] = new Token($word, $type);
                }

                continue;
            }

            // Fallback error if character does not match any known token types
            throw new SyntaxError("Unexpected character: $char");
        }

        return $tokens;
    }
}