<?php

declare(strict_types=1);


use Codewithkyrian\Jinja\Core\Environment;
use Codewithkyrian\Jinja\Core\Interpreter;
use Codewithkyrian\Jinja\Core\Lexer;
use Codewithkyrian\Jinja\Core\Parser;

beforeEach(function () {
    $this->env = new Environment();
    $this->env->set("True", true);
});

test('should handle whitespace control', function ($template, $data, $lstripBlocks, $trimBlocks, $target) {
    foreach ($data as $key => $value) {
        $this->env->set($key, $value);
    }

    $tokens = Lexer::tokenize($template, $lstripBlocks, $trimBlocks);
    $parsed = Parser::make($tokens)->parse();

    $interpreter = new Interpreter($this->env);

    $result = $interpreter->run($parsed);

    expect($result->value)->toEqual($target);
})->with('interpreterTestData');
