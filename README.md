# Jinja PHP

[![Build Status](https://github.com/CodeWithKyrian/jinja-php/actions/workflows/test.yml/badge.svg?branch=main)](https://github.com/CodeWithKyrian/jinja-php/actions/workflows/test.yml)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/CodeWithKyrian/jinja-php/)
[![GitHub license](https://img.shields.io/github/license/CodeWithKyrian/jinja-php.svg)](https://github.com/codewithkyrian/jinja-php/blob/main/LICENSE)
[![GitHub release](https://img.shields.io/github/release/CodeWithKyrian/jinja-php.svg)](https://github.com/byjg/php-jinja/releases/)

A **zero-dependency** PHP implementation of the Jinja templating engine, specifically designed for parsing and rendering
machine learning (ML) chat templates. This project is heavily inspired by HuggingFace's Jinja template engine in
JavaScript, intended primarily for ML chat templates, but is versatile enough to be used for general purposes of parsing
most Jinja templates.

## Installation

Install Jinja PHP through Composer:

```shell
composer require codewithkyrian/jinja-php
```

## Quick Start

Here's how you can use Jinja PHP to render a template:

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

## Features

### ✅ **Supported Features**

#### **Control Structures**
- **Conditional Statements**: `{% if %}`, `{% elif %}`, `{% else %}`, `{% endif %}`
- **For Loops**: `{% for %}`, `{% else %}`, `{% endfor %}` with loop variables (`loop.index`, `loop.index0`, `loop.first`, `loop.last`, `loop.length`, `loop.previtem`, `loop.nextitem`)
- **Break/Continue**: `{% break %}`, `{% continue %}` for loop control
- **Ternary Expressions**: `{{ value if condition else other_value }}`

#### **Variables & Data**
- **Variable Output**: `{{ variable }}`
- **Negative Array Indexing**: `messages[-1]`, `array[-2]` (Python-style)
- **Object/Array Access**: `user.name`, `messages[0]['content']`
- **Null Literals**: `none`, `None`
- **Boolean Literals**: `true`, `false`, `True`, `False`
- **String Concatenation**: `{{ "Hello" ~ " " ~ "World" }}`

#### **Macros & Functions**
- **Macros**: `{% macro name(arg1, arg2) %}...{% endmacro %}` with `{{ name() }}` calls
- **Function Calls**: `{{ function(arg1, arg2) }}`
- **Keyword Arguments**: `{{ function(param1=value1, param2=value2) }}`
- **Spread Arguments**: `{{ function(*args) }}`

#### **Filters**
- **String Filters**: `lower`, `upper`, `title`, `capitalize`, `strip`, `lstrip`, `rstrip`
- **String Manipulation**: `replace`, `split`, `startswith`, `endswith`, `indent`
- **Array Filters**: `length`, `join`, `map`, `reverse`, `sort`
- **Data Filters**: `tojson`, `default`
- **Custom Filter Blocks**: `{% filter filter_name %}...{% endfilter %}`

#### **Advanced Features**
- **Comments**: `{# This is a comment #}`
- **Set Statements**: `{% set variable = value %}` and block sets `{% set variable %}{% endset %}`
- **Call Blocks**: `{% call macro_name() %}...{% endcall %}`
- **Exception Handling**: `{{ raise_exception('Error message') }}`
- **String Concatenation**: Multiple string literals and `~` operator

#### **Built-in Functions**
- `range()`: Generate number sequences
- `raise_exception()`: Throw runtime exceptions
- `len()`: Get length of arrays/strings

### 🔄 **Partially Supported Features**

- **Complex Expressions**: Most Jinja expressions work, but some edge cases may differ
- **Template Inheritance**: Basic support (limited)
- **Custom Filters**: Can be added via the Environment class
- **Whitespace Control**: Basic support with `{%-` and `-%}`

### ❌ **Not Yet Supported**

- **Template Inheritance**: `{% extends %}`, `{% block %}`, `{% include %}`
- **Custom Functions**: User-defined functions
- **Advanced Filters**: Some complex filters like `groupby`, `batch`

## Usage Examples

### **Conditional Statements**

```php
{% if user.isActive %}
  Hello, {{ user.name }}!
{% elif user.isGuest %}
  Hello, Guest!
{% else %}
  Please log in.
{% endif %}
```

### **For Loops with Loop Variables**

```php
{% for user in users %}
  {{ loop.index }} - {{ user.name }}
  {% if loop.first %}First user{% endif %}
  {% if loop.last %}Last user{% endif %}
{% else %}
  No users found.
{% endfor %}
```

### **Macros**

```php
{% macro format_user(user) %}
  <div class="user">
    <h3>{{ user.name|title }}</h3>
    <p>{{ user.email|lower }}</p>
  </div>
{% endmacro %}

{{ format_user(current_user) }}
```

### **String Manipulation**

```php
{{ "Hello World!"|upper|replace("WORLD", "PHP") }}
{{ "  hello  "|strip|capitalize }}
{{ "apple,banana,cherry"|split(",")|join(" and ") }}
{{ "Hello" ~ " " ~ "World" }}
```

### **Array Operations**

```php
{% for item in items|sort %}
  {{ item }}
{% endfor %}

{{ messages[-1]['content'] }}  {# Last message #}
{{ array|length }}  {# Array length #}
```

### **Set Statements**

```php
{% set greeting = "Hello, " ~ user.name %}
{{ greeting }}

{% set user_info %}
  Name: {{ user.name }}
  Email: {{ user.email }}
{% endset %}
{{ user_info|indent(2) }}
```

### **Filter Blocks**

```php
{% filter upper %}
  This text will be uppercase
{% endfilter %}
```

### **Comments**

```php
{# This is a comment that won't appear in output #}
{{ variable }}  {# Inline comment #}
```

## Advanced Usage

### **Error Handling**

```php
{% if user.role == 'admin' %}
  Admin panel
{% else %}
  {{ raise_exception('Access denied') }}
{% endif %}
```

### **Complex Expressions**

```php
{{ (user.isActive and user.hasPermission) or user.isAdmin }}
{{ messages|length > 0 and messages[-1].role == 'user' }}
{{ "Hello" if user.name else "Guest" }}
```

## Testing

Jinja PHP comes with a comprehensive test suite to ensure functionality remains consistent and reliable. To run the tests:

```shell
composer test
```

The test suite includes:
- Unit tests for all language features
- End-to-end template processing tests
- Error handling and edge case tests

## Performance

Jinja PHP is designed for performance with:
- Zero external dependencies
- Efficient tokenization and parsing
- Optimized runtime execution
- Memory-conscious design

## Contributing

Jinja PHP is designed to be robust and feature-rich, offering support for a wide range of functionalities. If there's a feature you need that isn't currently implemented, we encourage you to [request it](https://github.com/codewithkyrian/jinja-php/issues/new). Additionally, if you're proficient with PHP and understand the internals of templating engines, consider contributing to the project by submitting a pull request with your proposed feature.

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
the [LICENSE](https://github.com/CodeWithKyrian/jinja-php/blob/main/LICENSE) file for more information.