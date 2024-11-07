<?php

namespace App;

class Constants
{
    private function __construct() {}

    /**
     * Used as a placeholder value to avoid updating the password hash if the user has made no changes.
     * It is possible to avoid using such a magic value by adding more state management, however this
     * is good enough for now
     */
    const PASSWORD_SENTINEL = '          ';
}
