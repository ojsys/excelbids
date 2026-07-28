<?php
/**
 * Home page. Sections render in the order set in the CMS, and any section
 * switched off in Website Content > Sections is skipped entirely.
 *
 * @var array<int,array<string,mixed>> $sections   Ordered, visible sections
 * @var array<string,mixed>            $data       Content for every section
 */

use App\Core\View;

// Numbered "ghost" backdrop digits follow the file sections, as in the design.
$fileIndex = 0;

foreach ($sections as $section) {
    $key = (string) $section['section_key'];
    $template = 'site/sections/' . $key;

    if (!is_file(EB_APP . '/Views/' . $template . '.php')) {
        continue;
    }

    // Only the numbered "FILE §nn" sections carry a ghost numeral.
    $numbered = in_array($key, ['about', 'services', 'sectors', 'process', 'qa', 'why', 'proof', 'faq'], true);
    if ($numbered) {
        $fileIndex++;
    }

    echo View::partial($template, array_merge($data, [
        'ghostNum' => $numbered ? sprintf('%02d', $fileIndex) : null,
    ]));
}
