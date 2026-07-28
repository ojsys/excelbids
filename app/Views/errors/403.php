<?php
/** @var string $errorMessage */

use App\Core\Settings;

$message = $errorMessage ?? 'You do not have permission to view this page.';
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Not permitted — <?= e(Settings::get('site_name', 'ExcelBids') ?? 'ExcelBids') ?></title>
<meta name="robots" content="noindex">
<?= App\Core\Branding::faviconTags() ?>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Public+Sans:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body>
<div class="error-page">
  <div>
    <div class="code">403</div>
    <h1>Access restricted.</h1>
    <p><?= e($message) ?></p>
    <a href="<?= e(path('admin')) ?>" class="btn btn-red">Back to the dashboard</a>
  </div>
</div>
</body>
</html>
