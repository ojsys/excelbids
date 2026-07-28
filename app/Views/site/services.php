<?php
/**
 * @var array<int,array<string,mixed>> $services
 * @var array<int,array<string,mixed>> $sectors
 */
?>
<div class="page-head">
  <div class="wrap">
    <div class="eyebrow">File §02</div>
    <h1 style="margin-top:8px;"><?= e(block('services_heading', 'Our Services')) ?></h1>
    <p>Twelve ways we help UK organisations win public and private sector work — from a single bid review to running your whole bid programme.</p>
  </div>
</div>

<section style="padding-top:28px;">
  <div class="wrap">
    <div class="svc-grid">
      <?php foreach ($services as $index => $service): ?>
        <div class="svc-tile">
          <span class="idx"><?= e(sprintf('%02d', $index + 1)) ?></span>
          <?= e($service['title']) ?>
          <?php if (!empty($service['description'])): ?><small><?= e($service['description']) ?></small><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($sectors): ?>
<section class="tint">
  <div class="wrap">
    <div class="section-head">
      <div class="file-num mono">FILE §03</div>
      <h2><?= e(block('sectors_heading', 'Sectors we work in')) ?></h2>
      <?php if ($body = block('sectors_body')): ?><p><?= e($body) ?></p><?php endif; ?>
    </div>
    <div class="sector-tags">
      <?php foreach ($sectors as $sector): ?>
        <span<?= (int) $sector['is_core'] === 1 ? ' class="core"' : '' ?>><?= e($sector['name']) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section style="padding-top:0;">
  <div class="wrap" style="padding-top:60px;">
    <div class="cta-wrap">
      <div class="cta-inner">
        <div>
          <h2><?= e(block('cta_heading')) ?></h2>
          <p class="sub"><?= e(block('cta_sub')) ?></p>
        </div>
        <div class="cta-actions">
          <a href="<?= e(path('consultation')) ?>" class="btn btn-red">Submit a Consultation Request</a>
        </div>
      </div>
    </div>
  </div>
</section>
