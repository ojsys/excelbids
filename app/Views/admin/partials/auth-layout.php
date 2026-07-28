<?php
/**
 * Bare layout for sign-in, forgotten password and reset screens.
 *
 * @var string      $content
 * @var string|null $pageTitle
 * @var string|null $brandSub
 */

use App\Core\Flash;
use App\Core\Settings;

$messages = Flash::messages();
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(($pageTitle ?? 'Sign in') . ' — ' . Settings::get('site_name', 'ExcelBids')) ?></title>
<meta name="robots" content="noindex, nofollow">
<?= App\Core\Branding::faviconTags() ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-brand">
      <a href="<?= e(path('/')) ?>" class="logo"><?= App\Core\Branding::logoHtml('auth') ?></a>
      <div class="sub"><?= e($brandSub ?? 'Bid Management System') ?></div>
    </div>

    <?php foreach ($messages as $message): ?>
      <div class="alert alert-<?= e($message['type']) ?>"><?= e($message['message']) ?></div>
    <?php endforeach; ?>

    <?= $content ?>
  </div>
</div>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
