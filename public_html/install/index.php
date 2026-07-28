<?php
/**
 * ExcelBids web installer.
 *
 * Runs before app/config.php exists, so it deliberately avoids the framework's
 * database layer until the credentials have been proven to work.
 *
 * Delete this folder once installation is finished.
 */

declare(strict_types=1);

define('EB_INSTALLER', true);
require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Csrf;

$root = EB_ROOT;
$configFile = EB_APP . '/config.php';
$alreadyInstalled = is_file($configFile);

$step = (int) ($_GET['step'] ?? 1);
$errors = [];
$notices = [];

/** Requirements the app cannot run without. */
function eb_requirements(): array
{
    $storage = EB_ROOT . '/storage';
    $appDir = EB_APP;

    return [
        ['label' => 'PHP 8.0 or newer', 'ok' => version_compare(PHP_VERSION, '8.0.0', '>='), 'value' => PHP_VERSION,
         'fix' => 'Set the PHP version in cPanel → Select PHP Version.'],
        ['label' => 'PDO MySQL extension', 'ok' => extension_loaded('pdo_mysql'), 'value' => extension_loaded('pdo_mysql') ? 'Loaded' : 'Missing',
         'fix' => 'Enable pdo_mysql in cPanel → Select PHP Version → Extensions.'],
        ['label' => 'mbstring extension', 'ok' => extension_loaded('mbstring'), 'value' => extension_loaded('mbstring') ? 'Loaded' : 'Missing',
         'fix' => 'Enable mbstring in cPanel → Select PHP Version → Extensions.'],
        ['label' => 'fileinfo extension', 'ok' => extension_loaded('fileinfo'), 'value' => extension_loaded('fileinfo') ? 'Loaded' : 'Missing',
         'fix' => 'Needed to verify uploaded files. Enable it in cPanel → Select PHP Version.'],
        ['label' => 'app/ folder writable', 'ok' => is_writable($appDir), 'value' => is_writable($appDir) ? 'Writable' : 'Read-only',
         'fix' => 'Set the app folder to permission 755 so config.php can be written.'],
        ['label' => 'storage/ folder writable', 'ok' => is_dir($storage) ? is_writable($storage) : is_writable(EB_ROOT),
         'value' => (is_dir($storage) ? is_writable($storage) : is_writable(EB_ROOT)) ? 'Writable' : 'Read-only',
         'fix' => 'Set the storage folder to permission 755 — uploads and logs are written there.'],
    ];
}

/** Split a .sql file into statements, ignoring comments. */
function eb_sql_statements(string $path): array
{
    $sql = (string) file_get_contents($path);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;

    $statements = [];
    foreach (explode(";\n", $sql) as $statement) {
        $statement = trim($statement);
        if ($statement !== '' && $statement !== ';') {
            $statements[] = $statement;
        }
    }
    return $statements;
}

// ---------------------------------------------------------------------------
// Handle submissions
// ---------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyInstalled) {
    if (!Csrf::check((string) ($_POST['_token'] ?? ''))) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        // --- Step 2: test the database connection ---
        if ($action === 'database') {
            $db = [
                'host'     => trim((string) ($_POST['db_host'] ?? 'localhost')),
                'port'     => (int) ($_POST['db_port'] ?? 3306),
                'name'     => trim((string) ($_POST['db_name'] ?? '')),
                'user'     => trim((string) ($_POST['db_user'] ?? '')),
                'password' => (string) ($_POST['db_password'] ?? ''),
                'charset'  => 'utf8mb4',
            ];

            if ($db['name'] === '' || $db['user'] === '') {
                $errors[] = 'Please enter the database name and username.';
            } else {
                try {
                    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']);
                    $pdo = new PDO($dsn, $db['user'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

                    // Warn rather than fail — re-running against a populated database is a real scenario.
                    $existing = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables
                                                   WHERE table_schema = DATABASE() AND table_name = 'users'")->fetchColumn();
                    $_SESSION['install_db'] = $db;
                    $_SESSION['install_db_has_tables'] = $existing > 0;

                    header('Location: ?step=3');
                    exit;
                } catch (PDOException $e) {
                    $errors[] = 'Could not connect: ' . $e->getMessage();
                }
            }
        }

        // --- Step 3: create tables, write config, create the admin account ---
        if ($action === 'finish') {
            $db = $_SESSION['install_db'] ?? null;

            $siteName = trim((string) ($_POST['site_name'] ?? 'ExcelBids'));
            $siteUrl = rtrim(trim((string) ($_POST['site_url'] ?? '')), '/');
            $adminName = trim((string) ($_POST['admin_name'] ?? ''));
            $adminEmail = trim((string) ($_POST['admin_email'] ?? ''));
            $adminPassword = (string) ($_POST['admin_password'] ?? '');
            $adminConfirm = (string) ($_POST['admin_password_confirm'] ?? '');

            if ($db === null) {
                $errors[] = 'The database step was not completed. Please start again.';
            }
            if ($adminName === '') {
                $errors[] = 'Please enter your name.';
            }
            if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }
            if (mb_strlen($adminPassword) < 10) {
                $errors[] = 'Your password must be at least 10 characters.';
            }
            if ($adminPassword !== $adminConfirm) {
                $errors[] = 'The two passwords do not match.';
            }

            if (!$errors) {
                try {
                    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']);
                    $pdo = new PDO($dsn, $db['user'], $db['password'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);

                    // 1. Schema
                    foreach (eb_sql_statements($root . '/database/schema.sql') as $statement) {
                        $pdo->exec($statement);
                    }

                    // 2. Seed content, only on a fresh database.
                    $hasContent = (int) $pdo->query('SELECT COUNT(*) FROM content_blocks')->fetchColumn() > 0;
                    if (!$hasContent) {
                        foreach (eb_sql_statements($root . '/database/seed.sql') as $statement) {
                            $pdo->exec($statement);
                        }
                    }

                    // 3. The administrator account.
                    $exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                    $exists->execute([mb_strtolower($adminEmail)]);

                    if ($exists->fetchColumn()) {
                        $notices[] = 'An account already existed for ' . $adminEmail . ' — its password was left unchanged.';
                    } else {
                        $insert = $pdo->prepare(
                            'INSERT INTO users (name, email, password_hash, role, is_active, created_at)
                             VALUES (?, ?, ?, ?, 1, ?)'
                        );
                        $insert->execute([
                            $adminName,
                            mb_strtolower($adminEmail),
                            password_hash($adminPassword, PASSWORD_DEFAULT),
                            'admin',
                            date('Y-m-d H:i:s'),
                        ]);
                    }

                    // 4. Carry the site details into settings.
                    $setting = $pdo->prepare('UPDATE settings SET value = ?, updated_at = ? WHERE `key` = ?');
                    foreach ([
                        'site_name'       => $siteName,
                        'contact_email'   => $adminEmail,
                        'notify_email'    => $adminEmail,
                        'mail_from_email' => $adminEmail,
                        'mail_from_name'  => $siteName,
                    ] as $key => $value) {
                        $setting->execute([$value, date('Y-m-d H:i:s'), $key]);
                    }

                    // 5. Write config.php last — its presence marks the install as done.
                    $config = "<?php\n"
                        . "/**\n * ExcelBids configuration — generated by the installer on " . date('j M Y, H:i') . ".\n"
                        . " * Keep this file out of version control: it holds your database password.\n */\n\n"
                        . "return [\n"
                        . "    'db' => [\n"
                        . "        'host'     => " . var_export($db['host'], true) . ",\n"
                        . "        'port'     => " . (int) $db['port'] . ",\n"
                        . "        'name'     => " . var_export($db['name'], true) . ",\n"
                        . "        'user'     => " . var_export($db['user'], true) . ",\n"
                        . "        'password' => " . var_export($db['password'], true) . ",\n"
                        . "        'charset'  => 'utf8mb4',\n"
                        . "    ],\n\n"
                        . "    'base_url'     => " . var_export($siteUrl, true) . ",\n"
                        . "    'base_path'    => '',\n"
                        . "    'timezone'     => 'Europe/London',\n"
                        . "    'debug'        => false,\n"
                        . "    'app_key'      => " . var_export(bin2hex(random_bytes(24)), true) . ",\n"
                        . "    'storage_path' => __DIR__ . '/../storage',\n\n"
                        . "    'session' => [\n"
                        . "        'name'     => 'excelbids_session',\n"
                        . "        'lifetime' => 7200,\n"
                        . "        'secure'   => " . (str_starts_with($siteUrl, 'https') ? 'true' : 'false') . ",\n"
                        . "    ],\n\n"
                        . "    'security' => [\n"
                        . "        'max_login_attempts' => 5,\n"
                        . "        'lockout_minutes'    => 15,\n"
                        . "    ],\n"
                        . "];\n";

                    if (@file_put_contents($configFile, $config) === false) {
                        $errors[] = 'The database is set up, but app/config.php could not be written. '
                                  . 'Set the app folder to permission 755 and try again, or create config.php by hand from config.sample.php.';
                    } else {
                        @chmod($configFile, 0640);
                        unset($_SESSION['install_db'], $_SESSION['install_db_has_tables']);
                        $_SESSION['install_complete'] = true;
                        header('Location: ?step=4');
                        exit;
                    }
                } catch (Throwable $e) {
                    $errors[] = 'Installation failed: ' . $e->getMessage();
                }
            }
        }
    }
}

$requirements = eb_requirements();
$allRequirementsMet = !in_array(false, array_column($requirements, 'ok'), true);
$token = Csrf::token();

// Suggest sensible defaults for the URL field.
$guessedUrl = (function (): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('/install/index.php', '', (string) ($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir;
})();
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Install ExcelBids</title>
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box;}
  body{margin:0;background:#FBFAF5;color:#1B1B17;font-family:'Public Sans',system-ui,sans-serif;
       font-size:15px;line-height:1.6;padding:40px 20px;}
  .wrap{max-width:660px;margin:0 auto;}
  h1,h2,h3{font-family:'Fraunces',Georgia,serif;font-weight:600;margin:0;letter-spacing:-0.01em;}
  .brand{text-align:center;margin-bottom:28px;}
  .brand .logo{font-family:'Fraunces',serif;font-weight:700;font-size:28px;}
  .brand .logo span{color:#B23A2E;}
  .brand .sub{font-family:'IBM Plex Mono',monospace;font-size:10.5px;letter-spacing:0.14em;
              text-transform:uppercase;color:#8A8677;margin-top:5px;}
  .steps{display:flex;gap:0;justify-content:center;margin-bottom:26px;position:relative;}
  .steps::before{content:"";position:absolute;top:15px;left:15%;right:15%;height:2px;background:#DFDACB;}
  .stp{flex:0 0 90px;text-align:center;position:relative;z-index:1;}
  .stp .pip{width:30px;height:30px;border-radius:50%;margin:0 auto 6px;background:#fff;
            border:2px solid #DFDACB;color:#8A8677;display:flex;align-items:center;justify-content:center;
            font-family:'IBM Plex Mono',monospace;font-size:12px;font-weight:600;}
  .stp.done .pip{background:#1B2A47;border-color:#1B2A47;color:#fff;}
  .stp.now .pip{background:#B23A2E;border-color:#B23A2E;color:#fff;box-shadow:0 0 0 4px #F4E3DF;}
  .stp .nm{font-size:11px;color:#5B584C;}
  .card{background:#fff;border:1px solid #DFDACB;border-radius:4px;margin-bottom:18px;
        box-shadow:0 20px 50px -34px rgba(20,20,15,.4);}
  .card-head{padding:18px 24px;border-bottom:1px solid #EDE9DD;}
  .card-head h2{font-size:18px;}
  .card-head p{margin:4px 0 0;font-size:13.5px;color:#5B584C;}
  .card-body{padding:24px;}
  .card-foot{padding:16px 24px;border-top:1px solid #EDE9DD;background:#FCFBF7;display:flex;gap:10px;align-items:center;}
  .field{margin-bottom:16px;}
  .field label{display:block;font-size:12.5px;font-weight:600;margin-bottom:5px;}
  .input{width:100%;padding:10px 12px;font-family:inherit;font-size:14px;background:#FBFAF5;
         border:1px solid #DFDACB;border-radius:3px;color:#1B1B17;}
  .input:focus{outline:none;border-color:#1B1B17;background:#fff;}
  .help{display:block;font-size:11.5px;color:#5B584C;margin-top:4px;}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
  .btn{display:inline-flex;align-items:center;justify-content:center;padding:11px 22px;border-radius:3px;
       font-size:14px;font-weight:600;border:1px solid transparent;cursor:pointer;text-decoration:none;font-family:inherit;}
  .btn-red{background:#B23A2E;color:#fff;} .btn-red:hover{background:#963025;}
  .btn-ghost{background:#fff;color:#1B1B17;border-color:#DFDACB;}
  .alert{padding:12px 16px;border-radius:3px;font-size:13.5px;margin-bottom:16px;border:1px solid;}
  .alert-error{background:#F4E3DF;border-color:#E0BDB6;color:#7E2318;}
  .alert-warn{background:#FBF3DF;border-color:#E4D3A4;color:#6A5312;}
  .alert-ok{background:#E8F1EC;border-color:#C4DCCF;color:#1F4A36;}
  .alert-info{background:#EAF0F8;border-color:#C6D2E4;color:#1B2A47;}
  table{width:100%;border-collapse:collapse;font-size:13.5px;}
  td{padding:9px 0;border-bottom:1px solid #EDE9DD;vertical-align:top;}
  tr:last-child td{border-bottom:none;}
  .pill{display:inline-block;padding:2px 9px;border-radius:10px;font-size:11.5px;font-weight:600;}
  .pill-ok{background:#E8F1EC;color:#2F6B4F;} .pill-no{background:#F4E3DF;color:#8C2A1F;}
  .mono{font-family:'IBM Plex Mono',monospace;}
  code{background:#F1EEE4;padding:1px 5px;border-radius:2px;font-size:12.5px;font-family:'IBM Plex Mono',monospace;}
  ol{padding-left:20px;} ol li{margin-bottom:9px;}
  .stamp{width:110px;height:110px;margin:0 auto 20px;border-radius:50%;border:3px double #2F6B4F;color:#2F6B4F;
         display:flex;align-items:center;justify-content:center;text-align:center;transform:rotate(-8deg);
         font-family:'IBM Plex Mono',monospace;font-weight:600;font-size:12px;letter-spacing:.05em;}
</style>
</head>
<body>
<div class="wrap">

  <div class="brand">
    <div class="logo">Excel<span>Bids</span></div>
    <div class="sub">Bid Management System — Installer</div>
  </div>

  <?php if ($alreadyInstalled && empty($_SESSION['install_complete'])): ?>
    <div class="card">
      <div class="card-head"><h2>Already installed</h2></div>
      <div class="card-body">
        <div class="alert alert-warn">
          <strong>A configuration file already exists.</strong>
          The installer will not run again — that would risk overwriting your live settings.
        </div>
        <p>If you need to reinstall, delete <code>app/config.php</code> first.</p>
        <p><strong>Otherwise, delete the <code>public_html/install</code> folder now.</strong></p>
      </div>
      <div class="card-foot">
        <a href="../admin/login" class="btn btn-red">Go to the admin panel</a>
        <a href="../" class="btn btn-ghost">View the website</a>
      </div>
    </div>
    </div></body></html>
    <?php exit; ?>
  <?php endif; ?>

  <div class="steps">
    <?php foreach ([1 => 'Requirements', 2 => 'Database', 3 => 'Your account', 4 => 'Done'] as $number => $name): ?>
      <div class="stp <?= $step > $number ? 'done' : ($step === $number ? 'now' : '') ?>">
        <div class="pip"><?= $step > $number ? '✓' : sprintf('%02d', $number) ?></div>
        <div class="nm"><?= htmlspecialchars($name) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endforeach; ?>
  <?php foreach ($notices as $notice): ?>
    <div class="alert alert-info"><?= htmlspecialchars($notice) ?></div>
  <?php endforeach; ?>

  <?php if ($step === 1): ?>
    <div class="card">
      <div class="card-head">
        <h2>Server requirements</h2>
        <p>Everything below must pass before ExcelBids can run.</p>
      </div>
      <div class="card-body">
        <table>
          <?php foreach ($requirements as $requirement): ?>
            <tr>
              <td style="width:210px;"><strong><?= htmlspecialchars($requirement['label']) ?></strong></td>
              <td style="width:110px;">
                <span class="pill <?= $requirement['ok'] ? 'pill-ok' : 'pill-no' ?>"><?= htmlspecialchars($requirement['value']) ?></span>
              </td>
              <td style="color:#5B584C;font-size:12.5px;">
                <?= $requirement['ok'] ? '' : htmlspecialchars($requirement['fix']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <div class="card-foot">
        <?php if ($allRequirementsMet): ?>
          <a href="?step=2" class="btn btn-red">Continue</a>
          <span style="font-size:13px;color:#2F6B4F;">All checks passed.</span>
        <?php else: ?>
          <a href="?step=1" class="btn btn-ghost">Re-check</a>
          <span style="font-size:13px;color:#8C2A1F;">Fix the items above, then re-check.</span>
        <?php endif; ?>
      </div>
    </div>

  <?php elseif ($step === 2): ?>
    <form method="post" action="?step=2">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($token) ?>">
      <input type="hidden" name="action" value="database">

      <div class="card">
        <div class="card-head">
          <h2>Database connection</h2>
          <p>Create a database and user in cPanel → MySQL® Databases, then enter the details here.</p>
        </div>
        <div class="card-body">
          <div class="alert alert-info">
            On cPanel your database and username are prefixed with your account name, for example
            <code>myaccount_excelbids</code>. Remember to add the user to the database with
            <strong>ALL PRIVILEGES</strong>.
          </div>

          <div class="row">
            <div class="field">
              <label for="db_host">Database host</label>
              <input class="input" type="text" id="db_host" name="db_host"
                     value="<?= htmlspecialchars((string) ($_POST['db_host'] ?? 'localhost')) ?>" required>
              <span class="help">Almost always <code>localhost</code> on cPanel.</span>
            </div>
            <div class="field">
              <label for="db_port">Port</label>
              <input class="input" type="number" id="db_port" name="db_port"
                     value="<?= htmlspecialchars((string) ($_POST['db_port'] ?? '3306')) ?>" required>
            </div>
          </div>

          <div class="field">
            <label for="db_name">Database name</label>
            <input class="input" type="text" id="db_name" name="db_name"
                   value="<?= htmlspecialchars((string) ($_POST['db_name'] ?? '')) ?>" required
                   placeholder="myaccount_excelbids">
          </div>

          <div class="row">
            <div class="field">
              <label for="db_user">Database username</label>
              <input class="input" type="text" id="db_user" name="db_user"
                     value="<?= htmlspecialchars((string) ($_POST['db_user'] ?? '')) ?>" required
                     placeholder="myaccount_ebuser">
            </div>
            <div class="field">
              <label for="db_password">Database password</label>
              <input class="input" type="password" id="db_password" name="db_password" autocomplete="off">
            </div>
          </div>
        </div>
        <div class="card-foot">
          <button type="submit" class="btn btn-red">Test connection &amp; continue</button>
          <a href="?step=1" class="btn btn-ghost">Back</a>
        </div>
      </div>
    </form>

  <?php elseif ($step === 3): ?>
    <?php if (empty($_SESSION['install_db'])): ?>
      <div class="alert alert-error">The database step was not completed. <a href="?step=2">Go back</a>.</div>
    <?php else: ?>
      <?php if (!empty($_SESSION['install_db_has_tables'])): ?>
        <div class="alert alert-warn">
          <strong>This database already contains ExcelBids tables.</strong>
          Existing tables and content will be left alone — only missing tables are created.
        </div>
      <?php endif; ?>

      <form method="post" action="?step=3">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($token) ?>">
        <input type="hidden" name="action" value="finish">

        <div class="card">
          <div class="card-head">
            <h2>Your site and administrator account</h2>
            <p>This is the account you will use to sign in to the admin panel.</p>
          </div>
          <div class="card-body">
            <div class="field">
              <label for="site_name">Site name</label>
              <input class="input" type="text" id="site_name" name="site_name"
                     value="<?= htmlspecialchars((string) ($_POST['site_name'] ?? 'ExcelBids')) ?>" required>
            </div>

            <div class="field">
              <label for="site_url">Website address</label>
              <input class="input" type="url" id="site_url" name="site_url"
                     value="<?= htmlspecialchars((string) ($_POST['site_url'] ?? $guessedUrl)) ?>" required>
              <span class="help">No trailing slash. Use the <code>https://</code> version once your SSL certificate is active.</span>
            </div>

            <div class="field">
              <label for="admin_name">Your name</label>
              <input class="input" type="text" id="admin_name" name="admin_name"
                     value="<?= htmlspecialchars((string) ($_POST['admin_name'] ?? '')) ?>" required>
            </div>

            <div class="field">
              <label for="admin_email">Your email address</label>
              <input class="input" type="email" id="admin_email" name="admin_email"
                     value="<?= htmlspecialchars((string) ($_POST['admin_email'] ?? '')) ?>" required>
              <span class="help">Used to sign in, and as the address that receives consultation requests.</span>
            </div>

            <div class="row">
              <div class="field">
                <label for="admin_password">Password</label>
                <input class="input" type="password" id="admin_password" name="admin_password" required autocomplete="new-password">
                <span class="help">At least 10 characters.</span>
              </div>
              <div class="field">
                <label for="admin_password_confirm">Confirm password</label>
                <input class="input" type="password" id="admin_password_confirm" name="admin_password_confirm" required autocomplete="new-password">
              </div>
            </div>
          </div>
          <div class="card-foot">
            <button type="submit" class="btn btn-red">Install ExcelBids</button>
            <a href="?step=2" class="btn btn-ghost">Back</a>
          </div>
        </div>
      </form>
    <?php endif; ?>

  <?php elseif ($step === 4): ?>
    <div class="card">
      <div class="card-body" style="text-align:center;padding:40px 30px;">
        <div class="stamp">INSTALL<br>COMPLETE</div>
        <h2 style="font-size:22px;">ExcelBids is ready.</h2>
        <p style="color:#5B584C;margin-top:10px;">
          The database is set up, your website content is loaded, and your administrator account has been created.
        </p>
      </div>
      <div class="card-foot" style="justify-content:center;">
        <a href="../admin/login" class="btn btn-red">Sign in to the admin panel</a>
        <a href="../" class="btn btn-ghost">View the website</a>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h2>Three things to do now</h2></div>
      <div class="card-body">
        <ol>
          <li>
            <strong>Delete this installer.</strong> In the cPanel File Manager, remove the
            <code>public_html/install</code> folder. Leaving it in place is a security risk.
          </li>
          <li>
            <strong>Turn on HTTPS.</strong> Run AutoSSL in cPanel, then uncomment the HTTPS redirect
            block near the top of <code>public_html/.htaccess</code>.
          </li>
          <li>
            <strong>Check your email settings.</strong> Go to Settings → Email in the admin panel and send
            a test message, so you know consultation requests will reach you.
          </li>
        </ol>
      </div>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
