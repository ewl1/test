<?php

namespace MiniCMS;

use PDO;

class ProfiledPDO extends PDO
{
    public int $queryCount = 0;
    public float $queryTime = 0;

    public function query(string $query, ?int $fetchMode = null, ...$fetch_mode_args): \PDOStatement|false
    {
        $start = microtime(true);
        $result = parent::query($query, $fetchMode, ...$fetch_mode_args);
        $this->queryTime += microtime(true) - $start;
        $this->queryCount++;

        return $result;
    }

    public function exec(string $statement): int|false
    {
        $start = microtime(true);
        $result = parent::exec($statement);
        $this->queryTime += microtime(true) - $start;
        $this->queryCount++;

        return $result;
    }

    public function prepare(string $query, array $options = []): \PDOStatement|false
    {
        $start = microtime(true);
        $statement = parent::prepare($query, $options);
        $this->queryTime += microtime(true) - $start;
        // Do not increment queryCount here, as prepare() does not execute a query.
        // The count will be incremented when PDOStatement::execute() is called.
        // I will need to create a ProfiledPDOStatement class to handle this.

        return $statement;
    }
}
