<?php
/**
 * ExcelBids configuration.
 *
 * The web installer writes app/config.php from this template. You can also copy
 * it by hand, fill in your cPanel database credentials and delete public_html/install.
 */

return [
    // ---------------------------------------------------------------------
    // Database (cPanel > MySQL Databases)
    // ---------------------------------------------------------------------
    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'cpaneluser_excelbids',
        'user'     => 'cpaneluser_ebuser',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    // ---------------------------------------------------------------------
    // Application
    // ---------------------------------------------------------------------

    // Public base URL, no trailing slash. Leave blank to auto-detect.
    'base_url' => '',

    // Sub-directory the app is served from, e.g. '/excelbids'. Blank for domain root.
    'base_path' => '',

    // Europe/London keeps deadlines in UK local time, including BST.
    'timezone' => 'Europe/London',

    // Set false on a live site so errors are logged instead of displayed.
    'debug' => false,

    // 32+ random characters. Rotating this invalidates all sessions and reset links.
    'app_key' => 'change-this-to-a-long-random-string',

    // Absolute path for uploads. Keep it outside the document root when possible.
    'storage_path' => __DIR__ . '/../storage',

    'session' => [
        'name'     => 'excelbids_session',
        'lifetime' => 7200,   // seconds of inactivity before logout
        'secure'   => false,  // set true once HTTPS is live
    ],

    'security' => [
        'max_login_attempts' => 5,
        'lockout_minutes'    => 15,
    ],
];
