<?php
declare(strict_types=1);

/**
 * Opens a PDO connection using the constants written by install.php
 * into config/database.local.php (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS).
 */
function get_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $localConfig = __DIR__ . '/../config/database.local.php';
    if (!file_exists($localConfig)) {
        http_response_code(500);
        exit('Database is not configured yet. Open install.php first.');
    }
    require_once $localConfig;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        defined('DB_PORT') ? DB_PORT : '3306',
        DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Could not connect to the database. Please try again later.');
    }

    return $pdo;
}
