<?php

define('FETCH_ASSOC', 2);
define('FETCH_COLUMN', 7);

class PgPdo {
    private $connection;

    public function __construct(array $config) {
        $parts = [];
        foreach (['host', 'port', 'dbname', 'user', 'password', 'sslmode'] as $key) {
            if (isset($config[$key]) && $config[$key] !== '') {
                $parts[] = $key . '=' . $config[$key];
            }
        }
        $connStr = implode(' ', $parts);

        $this->connection = @pg_connect($connStr);
        if (!$this->connection) {
            throw new Exception('Could not connect to PostgreSQL: ' . pg_last_error());
        }
    }

    public function prepare(string $sql): PgStatement {
        return new PgStatement($this->connection, $sql);
    }

    public function query(string $sql): PgStatement {
        $result = @pg_query($this->connection, $sql);
        if (!$result) {
            throw new Exception('Query failed: ' . pg_last_error($this->connection));
        }
        return new PgStatement($this->connection, $sql, $result);
    }

    public function beginTransaction(): bool {
        return (bool) pg_query($this->connection, 'BEGIN');
    }

    public function commit(): bool {
        return (bool) pg_query($this->connection, 'COMMIT');
    }

    public function rollBack(): bool {
        return (bool) pg_query($this->connection, 'ROLLBACK');
    }

    public function inTransaction(): bool {
        return pg_transaction_status($this->connection) === PGSQL_TRANSACTION_INTRANS;
    }

    public function lastInsertId(?string $sequence = null): string {
        $result = @pg_query($this->connection, 'SELECT lastval()');
        if (!$result) {
            throw new Exception('lastval failed: ' . pg_last_error($this->connection));
        }
        $row = pg_fetch_row($result);
        return $row[0] ?? '0';
    }
}

class PgStatement {
    private $connection;
    private $sql;
    private $result;

    public function __construct($connection, string $sql, $result = null) {
        $this->connection = $connection;
        $this->sql = $sql;
        $this->result = $result;
    }

    public function execute(array $params = []): bool {
        $sql = $this->convertPlaceholders($this->sql);
        $this->result = @pg_query_params($this->connection, $sql, $params);
        if (!$this->result) {
            throw new Exception('Execute failed: ' . pg_last_error($this->connection) . ' SQL: ' . $this->sql);
        }
        return true;
    }

    public function fetch(int $mode = FETCH_ASSOC): ?array {
        if (!$this->result) return null;
        $row = pg_fetch_assoc($this->result);
        return $row === false ? null : $row;
    }

    public function fetchAll(int $mode = FETCH_ASSOC): array {
        if (!$this->result) return [];
        if ($mode === FETCH_COLUMN) {
            $rows = [];
            while ($row = pg_fetch_row($this->result)) {
                $rows[] = $row[0];
            }
            return $rows;
        }
        $rows = [];
        while ($row = pg_fetch_assoc($this->result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function fetchColumn(int $column = 0) {
        if (!$this->result) return false;
        $row = pg_fetch_row($this->result);
        if ($row === false) return false;
        return $row[$column] ?? false;
    }

    public function rowCount(): int {
        if (!$this->result) return 0;
        return pg_affected_rows($this->result);
    }

    private function convertPlaceholders(string $sql): string {
        $i = 0;
        return preg_replace_callback('/\?/', function () use (&$i) {
            $i++;
            return '$' . $i;
        }, $sql);
    }
}

function getDb(): PgPdo {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $db = $config['db'];

    $pdo = new PgPdo([
        'host' => $db['host'],
        'port' => $db['port'],
        'dbname' => $db['database'],
        'user' => $db['username'],
        'password' => $db['password'],
        'sslmode' => $db['sslmode'] ?? 'disable',
    ]);

    return $pdo;
}
