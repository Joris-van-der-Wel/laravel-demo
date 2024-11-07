<?php

namespace App\Exceptions;

use Exception;

class ShareLoginRateLimited extends Exception
{
    public function __construct(public int $availableInSeconds, public $message) {
        parent::__construct();
    }
}
