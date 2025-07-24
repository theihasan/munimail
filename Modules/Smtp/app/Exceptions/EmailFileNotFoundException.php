<?php

namespace Modules\SMTP\Exceptions;

use Exception;

class EmailFileNotFoundException extends Exception {
    public function __construct(string $message = "Email file not found", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
