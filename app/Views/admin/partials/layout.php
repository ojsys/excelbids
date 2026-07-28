<?php
/**
 * Admin panel layout.
 *
 * @var string      $content
 * @var string|null $pageTitle    Browser title
 * @var string|null $heading      Topbar heading
 * @var string|null $crumb        Small label above the heading
 * @var string|null $active       Nav item to highlight
 * @var string|null $topActions   Raw HTML for the topbar action area
 */

use App\Core\Auth;
use App\Core\Branding;
use App\Core\Flash;
use App\Core\Settings;
use App\Models\Client;
use App\Models\Enquiry;

$user = Auth::user(Auth::STAFF);
$siteName = Settings::get('site_name', 'ExcelBids');
$active = $active ?? '';

// Badge counts, fetched once per page render.
$newEnquiries = Enquiry::newCount();
$unreadMessages = Client::unreadMessageCount();

/** One sidebar link. */
$nav = static function (string $key, string $href, string $icon, string $label, ?int $count = null, bool $alert = false) use ($active): void {
    $isActive = $active === $key;
    ?>
    <a class="nav-item<?= $isActive ? ' active' : '' ?>" href="<?= e(path($href)) ?>">
      <span class="ico" aria-hidden="true"><?= $icon ?></span>
      <span><?= e($label) ?></span>
      <?php if ($count !== null && $count > 0): ?>
        <span class="count<?= $alert ? ' alert' : '' ?>"><?= (int) $count ?></span>
      <?php endif; ?>
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
<title><?= e(($pageTitle ?? 'Dashboard') . ' — ' . $siteName . ' Admin') ?></title>
<meta name="robots" content="noindex, nofollow">
<?= App\Core\Branding::faviconTags() ?>
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
      <a href="<?= e(path('admin')) ?>" class="logo"><?= Branding::logoHtml('sidebar') ?></a>
      <span class="env">Bid Management System</span>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-group">
        <h6>Overview</h6>
        <?php $nav('dashboard', 'admin', '◧', 'Dashboard'); ?>
        <?php if (Auth::can('reports.view')) { $nav('reports', 'admin/reports', '◔', 'Reports'); } ?>
      </div>

      <div class="nav-group">
        <h6>Work</h6>
        <?php if (Auth::can('bids.view')) { $nav('bids', 'admin/bids', '▤', 'Bids'); } ?>
        <?php if (Auth::can('clients.view')) { $nav('clients', 'admin/clients', '◫', 'Clients'); } ?>
        <?php if (Auth::can('enquiries.view')) { $nav('enquiries', 'admin/enquiries', '✉', 'Consultation Requests', $newEnquiries, true); } ?>
        <?php if (Auth::can('messages.manage')) { $nav('messages', 'admin/messages', '❑', 'Client Messages', $unreadMessages, true); } ?>
      </div>

      <?php if (Auth::can('cms.manage') || Auth::can('settings.manage') || Auth::can('users.manage')): ?>
        <div class="nav-group">
          <h6>Configure</h6>
          <?php if (Auth::can('cms.manage')) { $nav('cms', 'admin/cms', '◈', 'Website Content'); } ?>
          <?php if (Auth::can('users.manage')) { $nav('users', 'admin/users', '◍', 'Staff Accounts'); } ?>
          <?php if (Auth::can('settings.manage')) { $nav('settings', 'admin/settings', '⚙', 'Settings'); } ?>
        </div>
      <?php endif; ?>

      <div class="nav-group">
        <h6>Site</h6>
        <a class="nav-item" href="<?= e(path('/')) ?>" target="_blank" rel="noopener">
          <span class="ico" aria-hidden="true">↗</span><span>View public site</span>
        </a>
      </div>
    </nav>

    <div class="sidebar-foot">
      <a href="<?= e(path('admin/account')) ?>" class="sidebar-user">
        <span class="avatar"><?= e(initials($user['name'] ?? '')) ?></span>
        <span class="who">
          <strong><?= e((string) ($user['name'] ?? '')) ?></strong>
          <span><?= e(labelize((string) ($user['role'] ?? ''))) ?></span>
        </span>
      </a>
      <form method="post" action="<?= e(path('admin/logout')) ?>">
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
        <?php if (!empty($crumb)): ?><div class="crumb"><?= e($crumb) ?></div><?php endif; ?>
        <h1><?= e($heading ?? $pageTitle ?? 'Dashboard') ?></h1>
      </div>
      <?php if (!empty($topActions)): ?>
        <div class="topbar-actions"><?= $topActions ?></div>
      <?php endif; ?>
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
