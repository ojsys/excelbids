<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;
?>
<div class="pb-block pb-spacer pb-spacer-<?= e(R::spacing(R::get($settings, 'height', 'normal'))) ?>" aria-hidden="true"></div>
