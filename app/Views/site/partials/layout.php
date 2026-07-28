<?php
/**
 * Public site layout.
 *
 * @var string      $content
 * @var string|null $pageTitle
 * @var string|null $metaDescription
 * @var string|null $bodyClass
 */

use App\Core\Auth;
use App\Core\Branding;
use App\Core\Database;
use App\Core\Settings;

$siteName = Settings::get('site_name', 'ExcelBids');
$tagline  = Settings::get('site_tagline', '');
$title    = $pageTitle ?? ($siteName . ($tagline ? ' — ' . $tagline : ''));
$metaDesc = $metaDescription ?? Settings::get('meta_description', '');
$contactEmail = Settings::get('contact_email', '');
$portalOn = Settings::bool('portal_enabled', true);
$analytics = Settings::get('google_analytics_id', '');

$primaryNav = Database::all(
    "SELECT label, url FROM menu_items WHERE location = 'primary' AND is_active = 1 ORDER BY sort_order, id"
);
$footerCompany = Database::all(
    "SELECT label, url FROM menu_items WHERE location = 'footer_company' AND is_active = 1 ORDER BY sort_order, id"
);
$footerStart = Database::all(
    "SELECT label, url FROM menu_items WHERE location = 'footer_start' AND is_active = 1 ORDER BY sort_order, id"
);
$footerPages = Database::all(
    'SELECT slug, title FROM pages WHERE is_published = 1 AND show_in_footer = 1 ORDER BY sort_order, id'
);

$messages = App\Core\Flash::messages();
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?></title>
<?php if ($metaDesc): ?><meta name="description" content="<?= e($metaDesc) ?>"><?php endif; ?>
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e((string) $metaDesc) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e((string) $siteName) ?>">
<?php if ($shareImage = Branding::shareImageUrl()): ?>
<meta property="og:image" content="<?= e($shareImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= e($shareImage) ?>">
<?php endif; ?>
<link rel="canonical" href="<?= e(url(ltrim($_SERVER['REQUEST_URI'] ?? '/', '/'))) ?>">
<?= Branding::faviconTags() ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Public+Sans:wght@400;500;600;700&family=Caveat:wght@600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<?php if ($analytics): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($analytics) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= e($analytics) ?>');
</script>
<?php endif; ?>
</head>
<body<?= isset($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?>>

<a class="skip-link" href="#main">Skip to main content</a>

<header>
  <div class="header-strip">
    <div class="wrap">
      <span><?= e(block('header_strip_left', 'Questions before you start?')) ?>
        <?php if ($contactEmail): ?><a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a><?php endif; ?>
      </span>
      <?php if ($portalOn): ?>
        <span><?= e(block('header_strip_right', 'Existing client?')) ?> <a href="<?= e(path('portal/login')) ?>">Log in here</a></span>
      <?php endif; ?>
    </div>
  </div>
  <nav>
    <a href="<?= e(path('/')) ?>" class="logo"><?= Branding::logoHtml('site') ?></a>

    <div class="navlinks" id="primary-nav">
      <?php foreach ($primaryNav as $item): ?>
        <a href="<?= e(str_starts_with($item['url'], 'http') ? $item['url'] : path($item['url'])) ?>"><?= e($item['label']) ?></a>
      <?php endforeach; ?>
    </div>

    <div class="navcta">
      <button class="nav-toggle" type="button" aria-controls="primary-nav" aria-expanded="false" aria-label="Toggle navigation">☰ Menu</button>
      <?php if ($portalOn): ?>
        <a href="<?= e(path(Auth::check(Auth::CLIENT) ? 'portal' : 'portal/login')) ?>" class="btn btn-ghost">
          <?= Auth::check(Auth::CLIENT) ? 'My Portal' : 'Client Login' ?>
        </a>
      <?php endif; ?>
      <a href="<?= e(path('consultation')) ?>" class="btn btn-primary"><?= e(block('nav_cta_label', 'Start a Consultation')) ?></a>
    </div>
  </nav>
</header>

<main id="main">
  <?php if ($messages): ?>
    <div class="wrap" style="padding-top:20px;">
      <?php foreach ($messages as $message): ?>
        <div class="alert alert-<?= e($message['type']) ?>"><?= e($message['message']) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?= $content ?>
</main>

<footer>
  <div class="wrap">
    <div class="foot-grid">
      <div>
        <div class="foot-logo"><?= Branding::logoUrl() !== null ? Branding::logoHtml('site') : e((string) $siteName) ?></div>
        <p><?= e(block('footer_blurb', '')) ?></p>
      </div>
      <div class="foot-col">
        <h5>Company</h5>
        <?php foreach ($footerCompany as $item): ?>
          <a href="<?= e(str_starts_with($item['url'], 'http') ? $item['url'] : path($item['url'])) ?>"><?= e($item['label']) ?></a>
        <?php endforeach; ?>
      </div>
      <div class="foot-col">
        <h5>Get Started</h5>
        <?php foreach ($footerStart as $item): ?>
          <?php if (!$portalOn && str_contains($item['url'], 'portal')) { continue; } ?>
          <a href="<?= e(str_starts_with($item['url'], 'http') ? $item['url'] : path($item['url'])) ?>"><?= e($item['label']) ?></a>
        <?php endforeach; ?>
      </div>
      <div class="foot-col">
        <h5>Contact</h5>
        <?php if ($contactEmail): ?><a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a><?php endif; ?>
        <?php if ($phone = Settings::get('contact_phone')): ?><a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a><?php endif; ?>
        <?php if ($location = Settings::get('contact_location')): ?><span style="display:block;font-size:13.5px;color:var(--ink-soft);"><?= e($location) ?></span><?php endif; ?>
      </div>
    </div>
    <div class="foot-bottom">
      <span><?= e(str_replace('{year}', date('Y'), block('footer_copyright', '© {year} ' . $siteName . '. All rights reserved.'))) ?></span>
      <span>
        <?php $links = []; foreach ($footerPages as $page) { $links[] = '<a href="' . e(path($page['slug'])) . '">' . e($page['title']) . '</a>'; } ?>
        <?= implode(' · ', $links) ?>
      </span>
    </div>
  </div>
</footer>

<script src="<?= e(asset('js/site.js')) ?>" defer></script>
</body>
</html>
