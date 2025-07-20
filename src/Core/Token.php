<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Core;

/**
 * Represents a single token in the template.
 */
class Token
{
    public const KEYWORDS = [
        'set' => TokenType::Set,
        'for' => TokenType::For,
        'in' => TokenType::In,
        'is' => TokenType::Is,
        'if' => TokenType::If,
        'else' => TokenType::Else,
        'endset' => TokenType::EndSet,
        'endif' => TokenType::EndIf,
        'elif' => TokenType::ElseIf,
        'endfor' => TokenType::EndFor,
        'and' => TokenType::And,
        'or' => TokenType::Or,
        'not' => TokenType::Not,
        'not in' => TokenType::NotIn,
        'macro' => TokenType::Macro,
        'endmacro' => TokenType::EndMacro,
        'break' => TokenType::Break,
        'continue' => TokenType::Continue,

        // Literals
        'true' => TokenType::BooleanLiteral,
        'false' => TokenType::BooleanLiteral,
        'none' => TokenType::NullLiteral,
        'True' => TokenType::BooleanLiteral,
        'False' => TokenType::BooleanLiteral,
        'None' => TokenType::NullLiteral,
    ];

    public const ORDERED_MAPPING_TABLE = [
        // Control sequences
        ["{%", TokenType::OpenStatement],
        ["%}", TokenType::CloseStatement],
        ["{{", TokenType::OpenExpression],
        ["}}", TokenType::CloseExpression],

        // Single character tokens
        ["(", TokenType::OpenParen],
        [")", TokenType::CloseParen],
        ["{", TokenType::OpenCurlyBracket],
        ["}", TokenType::CloseCurlyBracket],
        ["[", TokenType::OpenSquareBracket],
        ["]", TokenType::CloseSquareBracket],
        [",", TokenType::Comma],
        [".", TokenType::Dot],
        [":", TokenType::Colon],
        ["|", TokenType::Pipe],

        // Comparison operators
        ["<=", TokenType::ComparisonBinaryOperator],
        [">=", TokenType::ComparisonBinaryOperator],
        ["==", TokenType::ComparisonBinaryOperator],
        ["!=", TokenType::ComparisonBinaryOperator],
        ["<", TokenType::ComparisonBinaryOperator],
        [">", TokenType::ComparisonBinaryOperator],

        // Arithmetic operators
        ["+", TokenType::AdditiveBinaryOperator],
        ["-", TokenType::AdditiveBinaryOperator],
        ["*", TokenType::MultiplicativeBinaryOperator],
        ["/", TokenType::MultiplicativeBinaryOperator],
        ["%", TokenType::MultiplicativeBinaryOperator],

        // Assignment operator
        ["=", TokenType::Equals],
    ];

    public const ESCAPE_CHARACTERS = [
        "n" => "\n", // New line
        "t" => "\t", // Horizontal tab
        "r" => "\r", // Carriage return
        "b" => "\b", // Backspace
        "f" => "\f", // Form feed
        "v" => "\v", // Vertical tab
        "'" => "'", // Single quote
        '"' => '"', // Double quote
        "\\" => "\\", // Backslash
    ];


    public function __construct(
        public readonly string    $value,
        public readonly TokenType $type
    ) {}
}
