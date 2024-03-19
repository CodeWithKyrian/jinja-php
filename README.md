# Jinja PHP

A minimalistic **zero-dependency** PHP implementation of the Jinja templating engine, specifically designed for parsing and rendering
machine learning (ML) chat templates. This project is heavily inspired by HuggingFace's Jinja template engine in
JavaScript, intended primarily for ML chat templates, but is versatile enough to be used for general purposes of parsing
most Jinja templates.

## Installation

Install Jinja PHP through Composer:

```shell
composer require codewithkyrian/jinja-php
```

## Usage

Here’s how you can use Jinja PHP to render a template:

```php
$sourceString = "{{ bos_token }}{% for message in messages %}{% if (message['role'] == 'user') != (loop.index0 % 2 == 0) %}{{ raise_exception('Conversation roles must alternate user/assistant/user/assistant/...') }}{% endif %}{% if message['role'] == 'user' %}{{ '[INST] ' + message['content'] + ' [/INST]' }}{% elif message['role'] == 'assistant' %}{{ message['content'] + eos_token + ' ' }}{% else %}{{ raise_exception('Only user and assistant roles are supported!') }}{% endif %}{% endfor %}";
$args = [
    'messages' => [
        ['role' => 'user', 'content' => 'Hello!'],
        ['role' => 'assistant', 'content' => 'Hi! How are you?'],
        ['role' => 'user', 'content' => 'I am doing great.'],
        ['role' => 'assistant', 'content' => 'That is great to hear.'],
    ],
    "add_generation_prompt" => true,
    "bos_token" => "<s>",
    "eos_token" => "</s>",
    "unk_token" => "<unk>",
];

$template = new Template($sourceString);

$rendered = $template->render($args);
// <s>[INST] Hello! [/INST]Hi! How are you?</s> [INST] I am doing great. [/INST]That is great to hear.</s> 
```

## Advanced Usage

Jinja PHP supports various control structures from Jinja templates. Here are some examples:

### Conditional Statements:

```js
{% if user.isActive %}
  "Hello," {{ user.name }}!
{% else %}
  "Hello, Guest!"
{% endif %}

```

### For Loops

```js
<ul>
{% for user in users %}
  <li>{{ user.name }}</li>
{% else %}
  <li>No users found.</li>
{% endfor %}
</ul>
```

```js
{% for user in users %}
  {{ loop.index }} - {{ user.name }}
{% endfor %}
```

### Variable Filters

```js
{{ "Hello World!"|lower }}
```

## Comprehensive Feature Support

Jinja PHP is designed to be robust and feature-rich, offering support for a wide range of functionalities beyond the
basics of templating. This includes handling exceptions, using default string methods like `strip()` or `upper()`,
working with dictionaries, and much more. If there's a feature you need that isn't currently implemented in Jinja PHP,
we encourage you to [request it](https://github.com/codewithkyrian/jinja-php/issues/new). Additionally, if you're
proficient with PHP and understand the internals of templating engines, consider contributing to the project by
submitting a pull request with your proposed feature.

## Testing

Jinja PHP comes with a suite of tests to ensure functionality remains consistent and reliable. To run the tests:

```
composer test
```

## Contributors

- [Kyrian Obikwelu](https://github.com/CodeWithKyrian)
- Other contributors are welcome.

## Acknowledgements

- [Hugging Face](https://huggingface.co/) for their work on ML chat templates.
- The Jinja template engine for Python, which served as the primary inspiration for this project.

## Support

If you find any issues or have a question, feel free
to [open an issue](https://github.com/CodeWithKyrian/jinja-php/issues/new/choose) in the repo.

## License

This project is licensed under the MIT License. See
the [LICENSE](https://github.com/codewithkyrian/jinja-php/blob/main/LICENSE) file for more information.