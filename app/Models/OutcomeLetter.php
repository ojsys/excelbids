<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Outcome letters — award notifications and evaluator feedback published as
 * public proof of work.
 *
 * These are not client documents. A row only becomes public once a member of
 * staff has ticked "client has approved publication", so an upload can never
 * reach the website by accident.
 */
final class OutcomeLetter
{
    /**
     * Everything cleared for the public page.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function published(): array
    {
        return Database::all(
            'SELECT * FROM outcome_letters
             WHERE is_active = 1 AND is_approved = 1
             ORDER BY sort_order, id'
        );
    }

    public static function publishedCount(): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM outcome_letters WHERE is_active = 1 AND is_approved = 1',
            [],
            0
        );
    }

    /**
     * Which letters use an image — checked before the media library lets one be
     * deleted, so a live page is never left with a broken letter.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function usingMedia(int $mediaId): array
    {
        return Database::all(
            'SELECT id, title FROM outcome_letters WHERE media_id = ?',
            [$mediaId]
        );
    }
}
