<?php

declare(strict_types=1);


use Codewithkyrian\Jinja\Template;

test('Default Templates', function ($chatTemplate, $data, $target) {
    $template = new Template($chatTemplate);

    expect($template->render($data))->toBe($target);
})->with('defaultTemplates');

test('Custom Templates',function ($chatTemplate, $data, $target) {
    $template = new Template($chatTemplate);

    expect($template->render($data))->toBe($target);
})->with('customTemplates');