<?php

namespace App\MiniCMS;

use PDO;

/**
 * Module Settings Manager
 *
 * Universali klasė modulių nustatymams saugoti ir gauti.
 * Naudoja settings lentelę su "modulio_raktas" formatu.
 *
 * Pavyzdys:
 *   $settings = new ModuleSettings($pdo, 'downloads');
 *   $settings->set('max_file_size', 52428800);
 *   $maxSize = $settings->get('max_file_size', 52428800);
 */
class ModuleSettings
{
    private PDO $pdo;
    private string $moduleName;
    private array $cache = [];
    private array $defaults = [];

    public function __construct(PDO $pdo, string $moduleName, array $defaults = [])
    {
        $this->pdo = $pdo;
        $this->moduleName = $moduleName;
        $this->defaults = $defaults;
    }

    /**
     * Gauti nustatymą
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $fullKey = $this->buildKey($key);

        // Patikrinti cache
        if (isset($this->cache[$fullKey])) {
            return $this->cache[$fullKey];
        }

        // Patikrinti DB
        $stmt = $this->pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$fullKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->cache[$fullKey] = $row['setting_value'];
            return $row['setting_value'];
        }

        // Grąžinti numatytą reikšmę
        $default = $default ?? ($this->defaults[$key] ?? null);
        $this->cache[$fullKey] = $default;
        return $default;
    }

    /**
     * Išsaugoti nustatymą
     */
    public function set(string $key, mixed $value): bool
    {
        $fullKey = $this->buildKey($key);
        $value = (string)$value;

        // Tikrinu ar jau egzistuoja
        $stmt = $this->pdo->prepare("SELECT id FROM settings WHERE setting_key = ?");
        $stmt->execute([$fullKey]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $this->pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $result = $stmt->execute([$value, $fullKey]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $result = $stmt->execute([$fullKey, $value]);
        }

        // Atnaujinti cache
        if ($result) {
            $this->cache[$fullKey] = $value;
        }

        return $result;
    }

    /**
     * Ištrinti nustatymą
     */
    public function delete(string $key): bool
    {
        $fullKey = $this->buildKey($key);
        $stmt = $this->pdo->prepare("DELETE FROM settings WHERE setting_key = ?");
        $result = $stmt->execute([$fullKey]);

        if ($result) {
            unset($this->cache[$fullKey]);
        }

        return $result;
    }

    /**
     * Gauti visus modulio nustatymus
     */
    public function all(): array
    {
        $prefix = $this->moduleName . '_';
        $stmt = $this->pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE ?");
        $stmt->execute([$prefix . '%']);

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            // Pašalinti "modulio_" prefiksą iš rakto
            $key = substr($row['setting_key'], strlen($prefix));
            $result[$key] = $row['setting_value'];
            $this->cache[$row['setting_key']] = $row['setting_value'];
        }

        return $result;
    }

    /**
     * Nustatyti numatytas reikšmes
     */
    public function setDefaults(array $defaults): self
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * Gauti numatytas reikšmes
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Iš naujo įkelti cache iš duomenų bazės
     */
    public function reload(): void
    {
        $this->cache = [];
    }

    /**
     * Sukurti pilną raktą su modulio prefiksu
     */
    private function buildKey(string $key): string
    {
        return $this->moduleName . '_' . $key;
    }
}
