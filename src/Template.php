<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja;

use Codewithkyrian\Jinja\AST\Program;
use Codewithkyrian\Jinja\Core\Environment;
use Codewithkyrian\Jinja\Core\Interpreter;
use Codewithkyrian\Jinja\Core\Lexer;
use Codewithkyrian\Jinja\Core\Parser;
use Codewithkyrian\Jinja\Exceptions\RuntimeException;
use Codewithkyrian\Jinja\Runtime\StringValue;

class Template
{
    private Program $parsed;

    /**
     * The constructor takes a template string, tokenizes it, parses it into a program structure.
     *
     * @param string $template The template string.
     */
    public function __construct(string $template, bool $lstripBlocks = true, bool $trimBlocks = true)
    {
        $tokens = Lexer::tokenize($template, lstripBlocks: $lstripBlocks, trimBlocks: $trimBlocks);
        $this->parsed = Parser::make($tokens)->parse();
    }

    /**
     * Renders the template with the provided items as variables.
     *
     * @param ?array $items Associative array of user-defined variables.
     * @return string The rendered template.
     */
    public function render(?array $items): string
    {
        // Create a new environment for this template
        $env = new Environment();

        // Declare global variables
        $env->set('false', false);
        $env->set('False', false);
        $env->set('true', true);
        $env->set('True', true);
        $env->set('none', null);
        $env->set('None', null);
        $env->set('raise_exception', fn(string $args) => throw new RuntimeException($args));
        $env->set('range', range(...));

        // Add user-defined variables
        if ($items) {
            foreach ($items as $key => $value) {
                $env->set($key, $value);
            }
        }

        $interpreter = new Interpreter($env);

        $result = $interpreter->run($this->parsed);

        return $result->value;
    }
}
