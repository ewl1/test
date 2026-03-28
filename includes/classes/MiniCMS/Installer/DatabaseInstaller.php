<?php

namespace App\MiniCMS\Installer;

use PDO;
use RuntimeException;

class DatabaseInstaller
{
    private string $host;
    private string $database;
    private string $username;
    private string $password;
    private DatabaseSchema $schema;

    public function __construct(string $host, string $database, string $username, string $password, DatabaseSchema $schema)
    {
        $this->host = trim($host);
        $this->database = trim($database);
        $this->username = trim($username);
        $this->password = $password;
        $this->schema = $schema;
    }

    public function install(): void
    {
        $databaseName = $this->validatedDatabaseName();
        $server = $this->serverConnection();
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . $databaseName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $pdo = $this->connect();
        foreach ($this->schema->statements() as $statement) {
            $sql = trim((string)$statement);
            if ($sql === '') {
                continue;
            }

            $pdo->exec($sql);
        }
    }

    public function connect(): PDO
    {
        return new PDO(
            'mysql:host=' . $this->validatedHost() . ';dbname=' . $this->validatedDatabaseName() . ';charset=utf8mb4',
            $this->username,
            $this->password,
            $this->pdoOptions()
        );
    }

    private function serverConnection(): PDO
    {
        return new PDO(
            'mysql:host=' . $this->validatedHost() . ';charset=utf8mb4',
            $this->username,
            $this->password,
            $this->pdoOptions()
        );
    }

    private function pdoOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    private function validatedHost(): string
    {
        if ($this->host === '') {
            throw new RuntimeException('Nenurodytas DB_HOST.');
        }

        return $this->host;
    }

    private function validatedDatabaseName(): string
    {
        if ($this->database === '' || !preg_match('/^[A-Za-z0-9_]+$/', $this->database)) {
            throw new RuntimeException('Neteisingas DB_NAME. Leidziami tik raides, skaiciai ir _.');
        }

        return $this->database;
    }
}
