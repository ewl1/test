<?php

namespace App\MiniCMS\Installer;

use PDO;
use RuntimeException;

class AdminAccountInstaller
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(string $username, string $email, string $password): void
    {
        $username = trim($username);
        $email = mb_strtolower(trim($email));
        $password = (string)$password;

        if ($username === '' || $email === '' || $password === '') {
            throw new RuntimeException('Uzpildykite visus administratoriaus laukus.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Neteisingas administratoriaus el. pastas.');
        }

        if (mb_strlen($password) < 8) {
            throw new RuntimeException('Administratoriaus slaptazodis turi buti bent 8 simboliu.');
        }

        $lookup = $this->pdo->prepare('SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1');
        $lookup->execute([
            ':email' => $email,
            ':username' => $username,
        ]);

        if ($lookup->fetchColumn()) {
            throw new RuntimeException('Toks administratorius jau egzistuoja.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, password, admin_password, role_id, is_active, status, created_at)
            VALUES (:username, :email, :password, :admin_password, 1, 1, 'active', NOW())
        ");
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $passwordHash,
            ':admin_password' => $passwordHash,
        ]);
    }
}
