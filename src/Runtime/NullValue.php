<?php

declare(strict_types=1);


namespace Codewithkyrian\Jinja\Runtime;

class NullValue extends RuntimeValue {
    public string $type = "NullValue";

    public function __construct() {
        parent::__construct(null);
    }
}
