<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja\Runtime;

use Exception;

/**
 * Control flow exception thrown when a break statement is encountered in a loop.
 */
class BreakControl extends Exception
{
}
