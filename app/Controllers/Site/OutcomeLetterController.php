<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Content;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Schema;
use App\Core\Settings;
use App\Models\Media;
use App\Models\OutcomeLetter;

/**
 * The public Outcome Letters page.
 *
 * Only rows a staff member has marked as approved for publication are read, so
 * an uploaded letter cannot reach this page without a deliberate tick.
 */
final class OutcomeLetterController extends Controller
{
    protected string $layout = 'site/partials/layout';

    public function index(Request $request): void
    {
        Schema::migrate();

        $letters = [];
        foreach (OutcomeLetter::published() as $letter) {
            // Resolved here rather than in the view so a deleted file simply
            // renders the letter without its image.
            $letter['image_url'] = Media::url(
                $letter['media_id'] === null ? null : (int) $letter['media_id']
            );
            $letters[] = $letter;
        }

        $this->view('site/outcome-letters', [
            'letters'         => $letters,
            'pageTitle'       => Content::block('outcome_heading', 'Outcome Letters')
                                 . ' — ' . Settings::get('site_name', 'ExcelBids'),
            'metaDescription' => str_excerpt(Content::block('outcome_intro'), 155),
        ]);
    }
}
