<?php

namespace App\Service;

class PasswordValidator
{
    public function validate(string $password): ?string
    {
        if (mb_strlen($password) < 10
            || !preg_match('/[A-Za-z]/', $password)
            || !preg_match('/\d/', $password)
            || !preg_match('/[^A-Za-z0-9]/', $password)
        ) {
            return 'Password must be at least 10 characters and include letters, numbers, and symbols.';
        }

        return null;
    }
}
