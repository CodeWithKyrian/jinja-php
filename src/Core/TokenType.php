<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Core;

/**
 * Represents tokens that our language understands in parsing.
 */
enum TokenType: string
{

    case Text = "Text";
    case NumericLiteral = "NumericLiteral"; // e.g., 123
    case BooleanLiteral = "BooleanLiteral";// true or false
    case StringLiteral = "StringLiteral"; // 'string'
    case Identifier = "Identifier"; // Variables, functions, etc.
    case Equals = "Equals"; // =
    case OpenParen = "OpenParen"; // (
    case CloseParen = "CloseParen"; // )
    case OpenStatement = "OpenStatement"; // {%
    case CloseStatement = "CloseStatement"; // %}
    case OpenExpression = "OpenExpression"; // {{
    case CloseExpression = "CloseExpression"; // }}
    case OpenSquareBracket = "OpenSquareBracket"; // [
    case CloseSquareBracket = "CloseSquareBracket"; // ]
    case OpenCurlyBracket = "OpenCurlyBracket"; // {
    case CloseCurlyBracket = "CloseCurlyBracket"; // }
    case Comma = "Comma"; // ,
    case Dot = "Dot"; // .
    case Colon = "Colon"; // :
    case Pipe = "Pipe"; // |

    case CallOperator = "CallOperator"; // ()
    case AdditiveBinaryOperator = "AdditiveBinaryOperator"; // + -
    case MultiplicativeBinaryOperator = "MultiplicativeBinaryOperator"; // * / %
    case ComparisonBinaryOperator = "ComparisonBinaryOperator"; // < > <= >= == !=
    case UnaryOperator = "UnaryOperato"; // ! - +

    // Keywords
    case Set = "Set";
    case If = "If";
    case For = "For";
    case In = "In";
    case Is = "Is";
    case NotIn = "NotIn";
    case Else = "Else";
    case EndIf = "EndIf";
    case ElseIf = "ElseIf";
    case EndFor = "EndFor";
    case And = "And";
    case Or = "Or";
    case Not = "UnaryOperator";




}