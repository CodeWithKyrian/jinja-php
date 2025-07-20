<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\Runtime;

/**
 * Control flow exception thrown when a continue statement is encountered in a loop.
 */
class ContinueControl extends \Exception {}
