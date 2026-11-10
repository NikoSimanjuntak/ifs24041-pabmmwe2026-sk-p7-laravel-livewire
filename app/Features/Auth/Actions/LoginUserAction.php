<?php

namespace App\Features\Auth\Actions;

use Illuminate\Support\Facades\Auth;

class LoginUserAction
{
    public function execute(string $username, string $password, bool $remember = false): bool
    {
        if (! Auth::attempt(['username' => $username, 'password' => $password], $remember)) {
            return false;
        }

        session()->regenerate();

        return true;
    }
}
