<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Runtime;

class UndefinedValue extends RuntimeValue {
    public string $type = "UndefinedValue";

    public function __construct() {
        parent::__construct(null);
    }
}
