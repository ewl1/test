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
        if (!\function_exists('login')) {
            return [false, null, 'Auth login service is unavailable.'];
        }

        $ok = \login($email, $password);
        return [$ok, $ok ? $this->currentUser() : null, $ok ? null : (\auth_error() ?: 'Prisijungti nepavyko.')];
    }

    public function attemptAdminLogin(string $email, string $password): array
    {
        if (!\function_exists('login_admin')) {
            return [false, null, 'Admin auth service is unavailable.'];
        }

        $ok = \login_admin($email, $password);
        return [$ok, $ok ? $this->currentUser() : null, $ok ? null : (\auth_error() ?: 'Administratoriaus prisijungimas nepavyko.')];
    }
}
