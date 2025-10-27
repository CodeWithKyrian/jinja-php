<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

$config = new PhpCsFixer\Config();

$parallelConfig = PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect();

return $config
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'explicit_string_variable' => true,
    ])
    ->setParallelConfig($parallelConfig)
    ->setFinder($finder);