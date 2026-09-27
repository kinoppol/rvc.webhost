<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('CONFIG_FILE', BASE_PATH . '/config/config.php');
define('LOCK_FILE', BASE_PATH . '/storage/installed.lock');
define('MIGRATIONS_PATH', BASE_PATH . '/migrations');
define('APP_VERSION', '1.0.0');

require __DIR__ . '/helpers.php';
require __DIR__ . '/Migrator.php';
require __DIR__ . '/Settings.php';
require __DIR__ . '/Provisioner.php';
require __DIR__ . '/Runbook.php';

date_default_timezone_set('Asia/Bangkok');

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_name('shp_sess');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function is_installed(): bool
{
    return is_file(LOCK_FILE) && is_file(CONFIG_FILE);
}

function config(?string $key = null, mixed $default = null): mixed
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = is_file(CONFIG_FILE) ? (require CONFIG_FILE) : [];
    }
    if ($key === null) return $cfg;
    $val = $cfg;
    foreach (explode('.', $key) as $part) {
        if (!is_array($val) || !array_key_exists($part, $val)) return $default;
        $val = $val[$part];
    }
    return $val;
}

/** Creates a PDO connection from explicit parameters (used by the installer too). */
function make_pdo(array $db, bool $withDatabase = true): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $db['host'], (int)($db['port'] ?? 3306));
    if ($withDatabase) $dsn .= ';dbname=' . $db['name'];
    return new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) $pdo = make_pdo(config('db'));
    return $pdo;
}
