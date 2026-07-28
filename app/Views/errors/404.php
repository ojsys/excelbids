<?php
/** @var string $errorMessage */

use App\Core\Settings;

$message = $errorMessage ?? 'That page could not be found.';
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Page not found — <?= e(Settings::get('site_name', 'ExcelBids') ?? 'ExcelBids') ?></title>
<meta name="robots" content="noindex">
<?= App\Core\Branding::faviconTags() ?>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Public+Sans:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body>
<div class="error-page">
  <div>
    <div class="code">404</div>
    <h1>This file is not in the cabinet.</h1>
    <p><?= e($message) ?></p>
    <a href="<?= e(path('/')) ?>" class="btn btn-red">Back to the homepage</a>
  </div>
</div>
</body>
</html>
