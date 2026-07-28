<?php

namespace App\Core\Exceptions;

class SwooleExitException extends \Exception
{
    public function __construct(int $code = 200, string $message = "Graceful exit triggered")
    {
        parent::__construct($message, $code);
    }
}
