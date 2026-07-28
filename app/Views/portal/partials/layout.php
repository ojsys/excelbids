<?php
/**
 * Client portal layout. Deliberately lighter than the admin panel — clients
 * only ever see four screens.
 *
 * @var string      $content
 * @var string|null $pageTitle
 * @var string|null $heading
 * @var string|null $active
 */

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Settings;
use App\Models\Message;

$user = Auth::user(Auth::CLIENT);
$siteName = Settings::get('site_name', 'ExcelBids');
$active = $active ?? '';
$unread = Message::unreadForClient((int) $user['client_id']);
$messagingOn = Settings::bool('portal_messaging', true);

$nav = static function (string $key, string $href, string $icon, string $label, int $count = 0) use ($active): void {
    ?>
    <a class="nav-item<?= $active === $key ? ' active' : '' ?>" href="<?= e(path($href)) ?>">
      <span class="ico" aria-hidden="true"><?= $icon ?></span>
      <span><?= e($label) ?></span>
      <?php if ($count > 0): ?><span class="count alert"><?= (int) $count ?></span><?php endif; ?>
    </a>
    <?php
};

$messages = Flash::messages();
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(($pageTitle ?? 'Client portal') . ' — ' . $siteName) ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Public+Sans:wght@400;500;600;700&family=Caveat:wght@700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<a class="skip-link" href="#content">Skip to content</a>

<div class="shell">

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <a href="<?= e(path('portal')) ?>" class="logo">Excel<span>Bids</span></a>
      <span class="env">Client Portal</span>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-group">
        <h6><?= e(str_excerpt((string) $user['organisation'], 26)) ?></h6>
        <?php $nav('dashboard', 'portal', '◧', 'Overview'); ?>
        <?php $nav('bids', 'portal/bids', '▤', 'My bids'); ?>
        <?php $nav('documents', 'portal/documents', '▭', 'Documents'); ?>
        <?php if ($messagingOn) { $nav('messages', 'portal/messages', '❑', 'Messages', $unread); } ?>
      </div>

      <div class="nav-group">
        <h6>Account</h6>
        <?php $nav('account', 'portal/account', '◍', 'My details'); ?>
        <a class="nav-item" href="<?= e(path('/')) ?>" target="_blank" rel="noopener">
          <span class="ico" aria-hidden="true">↗</span><span>Main website</span>
        </a>
      </div>
    </nav>

    <div class="sidebar-foot">
      <div class="sidebar-user">
        <span class="avatar"><?= e(initials((string) $user['name'])) ?></span>
        <span class="who">
          <strong><?= e((string) $user['name']) ?></strong>
          <span><?= e(str_excerpt((string) $user['organisation'], 22)) ?></span>
        </span>
      </div>
      <form method="post" action="<?= e(path('portal/logout')) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="nav-item" style="width:100%;background:none;border:none;text-align:left;">
          <span class="ico" aria-hidden="true">⏻</span><span>Sign out</span>
        </button>
      </form>
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="sidebar-toggle" type="button" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">☰</button>
      <div>
        <div class="crumb"><?= e((string) $user['organisation']) ?></div>
        <h1><?= e($heading ?? $pageTitle ?? 'Client portal') ?></h1>
      </div>
    </header>

    <main class="content" id="content">
      <?php foreach ($messages as $message): ?>
        <div class="alert alert-<?= e($message['type']) ?>"><?= e($message['message']) ?></div>
      <?php endforeach; ?>

      <?= $content ?>
    </main>
  </div>

</div>

<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
