<?php
/**
 * Single front controller for the public site, admin panel and client portal.
 * Apache rewrites every unmatched request here (see .htaccess).
 */

declare(strict_types=1);

// Under `php -S` every request reaches this script, including assets and the
// installer. Returning false hands real files back to the built-in server, and
// directories are resolved to their index.php. Apache does both itself, via the
// mod_rewrite conditions in .htaccess and DirectoryIndex.
if (PHP_SAPI === 'cli-server') {
    $path = (string) parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $requested = __DIR__ . $path;

    if (is_file($requested)) {
        return false;
    }

    // Sub-directory index (the installer at /install/). Never the document root
    // itself, which would make this file require itself forever.
    $index = rtrim($requested, '/') . '/index.php';
    if (trim($path, '/') !== '' && is_dir($requested) && is_file($index) && realpath($index) !== __FILE__) {
        require $index;
        return true;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Request;
use App\Core\Router;

$router = new Router();
require EB_APP . '/routes.php';

$router->dispatch(new Request());
