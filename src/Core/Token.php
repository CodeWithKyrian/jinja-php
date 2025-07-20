<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Core;

/**
 * Represents a single token in the template.
 */
class Token
{
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
        ["~", TokenType::AdditiveBinaryOperator],
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
