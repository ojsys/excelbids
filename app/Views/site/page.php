<?php
/** @var array<string,mixed> $page */
?>
<div class="page-head">
  <div class="wrap">
    <h1><?= e($page['title']) ?></h1>
  </div>
</div>

<section style="padding-top:24px;">
  <div class="wrap">
    <article class="prose"><?= $page['body'] ?></article>
  </div>
</section>
