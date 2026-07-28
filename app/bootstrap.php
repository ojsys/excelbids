<?php
/**
 * Application bootstrap: configuration, autoloading, error handling, session.
 * Every entry point (public site, admin, portal, installer) includes this file.
 */

declare(strict_types=1);

define('EB_START', microtime(true));
define('EB_ROOT', dirname(__DIR__));
define('EB_APP', __DIR__);

require EB_APP . '/helpers.php';

// --- Configuration ---------------------------------------------------------

$configFile = EB_APP . '/config.php';
if (!is_file($configFile)) {
    // Not installed yet. The installer handles this; anything else gets a nudge.
    if (!defined('EB_INSTALLER')) {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>ExcelBids — not installed</title>'
           . '<style>body{font-family:system-ui,sans-serif;background:#FBFAF5;color:#1B1B17;'
           . 'display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}'
           . 'div{max-width:34rem;padding:2rem;border:1px solid #DFDACB;background:#fff;border-radius:4px}'
           . 'a{color:#B23A2E}</style>'
           . '<div><h1>ExcelBids is not installed yet</h1>'
           . '<p>No <code>app/config.php</code> was found. Run the installer at '
           . '<a href="install/">/install/</a> to create it.</p></div>';
        exit;
    }
    $config = [];
} else {
    $config = require $configFile;
}

$config = array_replace_recursive([
    'db'          => ['host' => 'localhost', 'port' => 3306, 'name' => '', 'user' => '', 'password' => '', 'charset' => 'utf8mb4'],
    'base_url'    => '',
    'base_path'   => '',
    'timezone'    => 'Europe/London',
    'debug'       => false,
    'app_key'     => '',
    'storage_path' => EB_ROOT . '/storage',
    'session'     => ['name' => 'excelbids_session', 'lifetime' => 7200, 'secure' => false],
    'security'    => ['max_login_attempts' => 5, 'lockout_minutes' => 15],
], is_array($config) ? $config : []);

date_default_timezone_set($config['timezone']);
mb_internal_encoding('UTF-8');

// --- Error handling --------------------------------------------------------

$logDir = rtrim((string) $config['storage_path'], '/') . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

ini_set('log_errors', '1');
ini_set('error_log', $logDir . '/php-error.log');
error_reporting(E_ALL);
ini_set('display_errors', $config['debug'] ? '1' : '0');

set_exception_handler(static function (Throwable $e) use ($config): void {
    error_log(sprintf(
        "[%s] %s: %s in %s:%d\n%s",
        date('c'),
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    ));

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }

    if ($config['debug']) {
        echo '<pre style="padding:1.5rem;font:13px/1.6 ui-monospace,monospace;background:#1B1B17;color:#F2E08F;">';
        echo eb_e(get_class($e) . ': ' . $e->getMessage()) . "\n\n";
        echo eb_e($e->getFile() . ':' . $e->getLine()) . "\n\n";
        echo eb_e($e->getTraceAsString());
        echo '</pre>';
    } else {
        $view = EB_APP . '/Views/errors/500.php';
        if (is_file($view)) {
            include $view;
        } else {
            echo 'An unexpected error occurred. Please try again.';
        }
    }
    exit(1);
});

set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// --- Autoloader ------------------------------------------------------------

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = EB_APP . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

// --- Container-lite --------------------------------------------------------

App\Core\Config::load($config);

if (!defined('EB_INSTALLER') || App\Core\Config::get('db.name') !== '') {
    App\Core\Database::configure(App\Core\Config::get('db'));
}

// --- Session ---------------------------------------------------------------

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $sessionCfg = App\Core\Config::get('session');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_name((string) $sessionCfg['name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => App\Core\Config::basePath() . '/',
        'domain'   => '',
        'secure'   => (bool) $sessionCfg['secure'] || $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', (string) $sessionCfg['lifetime']);
    session_start();

    // Idle timeout — independent of the browser cookie lifetime.
    $idleLimit = (int) $sessionCfg['lifetime'];
    if (isset($_SESSION['_last_seen']) && (time() - (int) $_SESSION['_last_seen']) > $idleLimit) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['_expired'] = true;
    }
    $_SESSION['_last_seen'] = time();
}
