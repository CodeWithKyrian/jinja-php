# Changelog

All notable changes to `jinja-php` will be documented in this file.

## v2.1.0 - 2025-10-27

### What's Changed

* [Fix] Object literal interpretation by @drjamesj in https://github.com/CodeWithKyrian/jinja-php/pull/4
* Feat: improve code quality and fix runtime argument passing bugs by @CodeWithKyrian in https://github.com/CodeWithKyrian/jinja-php/pull/5

### New Contributors

* @drjamesj made their first contribution in https://github.com/CodeWithKyrian/jinja-php/pull/4

**Full Changelog**: https://github.com/CodeWithKyrian/jinja-php/compare/2.0.0...2.1.0

## 2.0.0 - 2025-07-20

### What's New

**Core Language Features**

- Added macro support with `{% macro %}` and `{% endmacro %}` blocks
- Added break and continue statement support for loops
- Added ternary expressions and improved `if-else` parsing with `elif` support
- Added spread expressions (`*args`) for function argument unpacking
- Added comment support with `{# #}` syntax
- Added null literal support (`none`, `None`)

**Template Processing**

- Added custom transformers-specific `generation` tag support
- Added string concatenation operator (`~`) and multiple string literal support
- Enhanced set statements with body support and `{% endset %}` blocks
- Added `for-else` loops with `{% else %}` blocks

**String Manipulation**

- Added new filters: `capitalize`, `replace`, `split` with maxsplit, `join`
- Enhanced `startswith`/`endswith` filters with tuple argument support
- Added `items`, `keys`, `values` methods for object iteration
- Added trimming functions: `strip`, `lstrip`, `rstrip`

**Data Processing**

- Added Python-style negative array indexing (e.g., `messages[-1]`)
- Added new filters: `tojson`, `map`, `indent`
- Added proper numeric type handling with `IntegerValue` and `FloatValue`
- Added support for both lowercase and uppercase boolean literals

### Fixes

- Fixed undefined array key errors in parser and lexer with proper bounds checking
- Improved error messages and exception handling throughout
- Enhanced parser architecture with better statement detection
- Added `JsonSerializable` interface to runtime values

### Breaking Changes

None - all changes are backward compatible.

### New Contributors

* @dkeetonx made their first contribution in https://github.com/CodeWithKyrian/jinja-php/pull/3

**Full Changelog**: https://github.com/CodeWithKyrian/jinja-php/compare/1.0.0...2.0.0

## v1.0.0 - 2024-03-19

### Initial Release

- Jinja Syntax Support - It supports most of the Jinja Syntax
- ML Chat Templates Ready - It has been tested against most of the Chat Templates on HuggingFace
- General Templating: It is versatile enough to be used for regular templating tasks.

**Full Changelog**: https://github.com/CodeWithKyrian/jinja-php/commits/1.0.0
