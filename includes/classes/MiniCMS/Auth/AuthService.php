<?php

namespace App\MiniCMS\Auth;

class AuthService
{
    public function currentUser(): ?array
    {
        return \function_exists('current_user') ? (\current_user() ?: null) : null;
    }

    public function attemptLogin(string $email, string $password): array
    {
        if (!\function_exists('attempt_login')) {
            return [false, null, 'Auth login service is unavailable.'];
        }

        return \attempt_login($email, $password);
    }

    public function attemptAdminLogin(string $email, string $password): array
    {
        if (!\function_exists('attempt_admin_login')) {
            return [false, null, 'Admin auth service is unavailable.'];
        }

        return \attempt_admin_login($email, $password);
    }
}
