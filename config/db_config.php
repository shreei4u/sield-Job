<?php
/**
 * Shield Job Portal — Database Connection Config
 * ------------------------------------------------
 * Works two ways, so the SAME codebase runs via Docker/GitHub or on
 * traditional shared hosting:
 *
 *  1. DOCKER / any host that supports real env vars:
 *     Set DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS as environment
 *     variables (docker-compose.yml already does this for you).
 *
 *  2. SHARED HOSTING (no env var support, e.g. cPanel/StackCP):
 *     Copy config/db_config.local.example.php to config/db_config.local.php
 *     and fill in real values — OR just run install.php once, which
 *     writes that file for you. db_config.local.php is gitignored, so
 *     real credentials never get committed.
 */

function env_or_const(string $envKey, ?string $fallbackConst, $default = null) {
    $val = getenv($envKey);
    if ($val !== false && $val !== '') {
        return $val;
    }
    if ($fallbackConst !== null && defined($fallbackConst)) {
        return constant($fallbackConst);
    }
    return $default;
}

$localOverride = __DIR__ . '/db_config.local.php';
if (file_exists($localOverride)) {
    require_once $localOverride; // defines DB_*_LOCAL constants — shared hosting path
}

define('DB_HOST', env_or_const('DB_HOST', 'DB_HOST_LOCAL', '127.0.0.1'));
define('DB_PORT', env_or_const('DB_PORT', 'DB_PORT_LOCAL', '3306'));
define('DB_NAME', env_or_const('DB_NAME', 'DB_NAME_LOCAL', 'shield_jobportal'));
define('DB_USER', env_or_const('DB_USER', 'DB_USER_LOCAL', 'root'));
define('DB_PASS', env_or_const('DB_PASS', 'DB_PASS_LOCAL', ''));
define('DB_CHARSET', 'utf8mb4');

function getDbConnection(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        die('Service temporarily unavailable.');
    }

    return $pdo;
}
