<?php

declare(strict_types=1);


use Codewithkyrian\Jinja\Core\Environment;
use Codewithkyrian\Jinja\Core\Interpreter;
use Codewithkyrian\Jinja\Core\Lexer;
use Codewithkyrian\Jinja\Core\Parser;
use Codewithkyrian\Jinja\Exceptions\ParserException;
use Codewithkyrian\Jinja\Exceptions\RuntimeException;
use Codewithkyrian\Jinja\Exceptions\SyntaxError;

describe('Lexical Errors', function (){
    test('Missing closing curly brace', function () {
        $text = "{{ variable";
        expect(fn() => Lexer::tokenize($text))->toThrow(SyntaxError::class);
    });

    test('Unclosed string literal', function () {
        $text = "{{ 'unclosed string }}";
        expect(fn() => Lexer::tokenize($text))->toThrow(SyntaxError::class);
    });

    test('Unexpected character', function () {
        $text = "{{ invalid ! invalid }}";
        expect(fn() => Lexer::tokenize($text))->toThrow(SyntaxError::class);
    });

    test('Invalid quote character', function () {
        $text = "{{ \u2018text\u2019 }}";
        expect(fn() => Lexer::tokenize($text))->toThrow(SyntaxError::class);
    });
});

describe('Parsing Errors', function () {
    // Parsing errors
    test('Unclosed statement', function () {
        $text = "{{ variable }}{{";
        $tokens = Lexer::tokenize($text);
        expect(fn() => Parser::make($tokens)->parse())->toThrow(SyntaxError::class);
    });

    test('Unclosed expression', function () {
        $text = "{% if condition %}\n    Content";
        $tokens = Lexer::tokenize($text);
        expect(fn() => Parser::make($tokens)->parse())->toThrow(ParserException::class);
    });

    test('Unmatched control structure', function () {
        $text = "{% if condition %}\n    Content\n{% endif %}\n{% endfor %}";
        $tokens = Lexer::tokenize($text);
        expect(fn() => Parser::make($tokens)->parse())->toThrow(SyntaxError::class);
    });

//    test('Missing variable in for loop', function () {
//        $text = "{% for %}\n    Content\n{% endfor %}";
//        $tokens = Lexer::tokenize($text);
//        expect(fn() => Parser::make($tokens)->parse())->toThrow(ParserException::class);
//    });

//    test('Unclosed parentheses in expression', function () {
//        $text = "{{ (variable + 1 }}";
//        $tokens = Lexer::tokenize($text);
//        expect(fn() => Parser::make($tokens)->parse())->toThrow(SyntaxError::class);
//    });

    test('Invalid variable name', function () {
        $text = "{{ 1variable }}";
        $tokens = Lexer::tokenize($text);
        expect(fn() => Parser::make($tokens)->parse())->toThrow(ParserException::class);
    });

//    test('Invalid control structure usage', function () {
//        $text = "{% if %}Content{% endif %}";
//        $tokens = Lexer::tokenize($text);
//        expect(fn() => Parser::make($tokens)->parse())->toThrow(SyntaxError::class);
//    });
});

describe('Runtime Errors', function () {
    // Runtime errors
    test('Undefined function call', function () {
        $env = new Environment();
        $interpreter = new Interpreter($env);
        $tokens = Lexer::tokenize("{{ undefined_function() }}");
        $ast = Parser::make($tokens)->parse();
        expect(fn() => $interpreter->run($ast))->toThrow(RuntimeException::class);
    });

    test('Incorrect function call', function () {
        $env = new Environment();
        $env->set("true", true);

        $interpreter = new Interpreter($env);
        $tokens = Lexer::tokenize("{{ true() }}");
        $ast = Parser::make($tokens)->parse();
        expect(fn() => $interpreter->run($ast))->toThrow(RuntimeException::class);
    });

    test('Looping over non-iterable', function () {
        $env = new Environment();
        $interpreter = new Interpreter($env);
        $env->set("non_iterable", 10);

        $tokens = Lexer::tokenize("{% for item in non_iterable %}{{ item }}{% endfor %}");
        $ast = Parser::make($tokens)->parse();
        expect(fn() => $interpreter->run($ast))->toThrow(RuntimeException::class);
    });

    test('Invalid variable assignment', function () {
        $env = new Environment();
        $interpreter = new Interpreter($env);

        $tokens = Lexer::tokenize("{% set 42 = variable %}");
        $ast =Parser::make($tokens)->parse();
        expect(fn() => $interpreter->run($ast))->toThrow(RuntimeException::class);
    }); 
});