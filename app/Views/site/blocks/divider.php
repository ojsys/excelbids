<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;
?>
<hr class="pb-block pb-divider pb-divider-<?= e(R::get($settings, 'style', 'line')) ?>">
