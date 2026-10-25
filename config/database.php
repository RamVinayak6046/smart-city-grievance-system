<?php
/**
 * CivicFix Pro — Flexible Database Adapter (MySQL with seamless SQLite fallback)
 */

define('DB_DRIVER',   getenv('DB_DRIVER') ?: 'sqlite'); // 'mysql' or 'sqlite'
define('DB_HOST',     getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME',     getenv('DB_NAME') ?: 'civicfix');
define('DB_USER',     getenv('DB_USER') ?: 'root');
define('DB_PASS',     getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET',  'utf8mb4');
define('SQLITE_PATH', getenv('SQLITE_PATH') ?: (dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'civicfix.sqlite'));

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        if (DB_DRIVER === 'mysql') {
            try {
                $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                return $pdo;
            } catch (PDOException $e) {
                error_log('[CivicFix MySQL failed, falling back to SQLite] ' . $e->getMessage());
            }
        }

        try {
            $pdo = new PDO('sqlite:' . SQLITE_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec("PRAGMA foreign_keys = ON;");
            $pdo->exec("PRAGMA journal_mode = WAL;");
            $pdo->exec("PRAGMA busy_timeout = 5000;");
        } catch (PDOException $e) {
            error_log('[CivicFix DB Error] ' . $e->getMessage());
            http_response_code(503);
            die('<h2 style="font-family:sans-serif;color:#c0392b;padding:2rem">Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</h2>');
        }
    }

    return $pdo;
}
