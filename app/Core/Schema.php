<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Idempotent schema upgrades.
 *
 * A site owner on cPanel should never have to open phpMyAdmin to apply an
 * update. Each step below checks the current state before acting, so running
 * migrate() repeatedly is harmless, and the version is recorded so the checks
 * cost nothing once applied.
 */
final class Schema
{
    /** Bump when a step is added. */
    private const VERSION = 3;

    private static bool $checkedThisRequest = false;

    /** Run any outstanding upgrades. Cheap to call — the version read is cached. */
    public static function migrate(): void
    {
        if (self::$checkedThisRequest) {
            return;
        }
        self::$checkedThisRequest = true;

        $current = (int) (Settings::get('schema_version', '0') ?? '0');
        if ($current >= self::VERSION) {
            return;
        }

        try {
            if ($current < 1) {
                self::stepOne();
            }
            if ($current < 2) {
                self::stepTwo();
            }
            if ($current < 3) {
                self::stepThree();
            }

            Settings::set('schema_version', (string) self::VERSION);
            Settings::flush();
        } catch (\Throwable $e) {
            // A failed upgrade must not take the whole panel down; log and carry on
            // with whatever the database currently supports.
            error_log('[schema] upgrade failed: ' . $e->getMessage());
        }
    }

    /** Brand assets. Settings rows are handled by Branding::ensureSettings(). */
    private static function stepOne(): void
    {
        // Nothing structural — recorded so the version numbering stays honest.
    }

    /** The page builder: block and media tables, plus the new page columns. */
    private static function stepTwo(): void
    {
        if (!self::hasTable('page_blocks')) {
            Database::run(
                "CREATE TABLE `page_blocks` (
                    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `page_id`      INT UNSIGNED NOT NULL,
                    `parent_id`    INT UNSIGNED NULL,
                    `block_type`   VARCHAR(40) NOT NULL,
                    `column_index` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                    `sort_order`   INT NOT NULL DEFAULT 0,
                    `settings`     MEDIUMTEXT NULL,
                    `is_visible`   TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at`   DATETIME NOT NULL,
                    `updated_at`   DATETIME NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_blocks_page` (`page_id`, `parent_id`, `column_index`, `sort_order`),
                    CONSTRAINT `fk_blocks_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_blocks_parent` FOREIGN KEY (`parent_id`) REFERENCES `page_blocks` (`id`) ON DELETE CASCADE
                 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        }

        if (!self::hasTable('media')) {
            Database::run(
                "CREATE TABLE `media` (
                    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `original_name` VARCHAR(255) NOT NULL,
                    `stored_name`   VARCHAR(255) NOT NULL,
                    `mime_type`     VARCHAR(120) NOT NULL DEFAULT '',
                    `size_bytes`    INT UNSIGNED NOT NULL DEFAULT 0,
                    `width`         INT UNSIGNED NULL,
                    `height`        INT UNSIGNED NULL,
                    `alt_text`      VARCHAR(255) NOT NULL DEFAULT '',
                    `uploaded_by`   INT UNSIGNED NULL,
                    `created_at`    DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_media_created` (`created_at`),
                    CONSTRAINT `fk_media_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
                 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        }

        // Existing pages keep their hand-written HTML; only new ones use blocks.
        if (!self::hasColumn('pages', 'layout_mode')) {
            Database::run("ALTER TABLE `pages` ADD COLUMN `layout_mode` ENUM('blocks','html') NOT NULL DEFAULT 'blocks' AFTER `title`");
            Database::run("UPDATE `pages` SET `layout_mode` = 'html' WHERE `body` IS NOT NULL AND `body` <> ''");
        }
        if (!self::hasColumn('pages', 'show_page_header')) {
            Database::run("ALTER TABLE `pages` ADD COLUMN `show_page_header` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_in_footer`");
        }
        if (!self::hasColumn('pages', 'hero_eyebrow')) {
            Database::run("ALTER TABLE `pages` ADD COLUMN `hero_eyebrow` VARCHAR(120) NOT NULL DEFAULT '' AFTER `show_page_header`");
        }
        if (!self::hasColumn('pages', 'hero_intro')) {
            Database::run("ALTER TABLE `pages` ADD COLUMN `hero_intro` VARCHAR(500) NOT NULL DEFAULT '' AFTER `hero_eyebrow`");
        }
    }

    /** Build About, Services and Contact pages using the block builder. */
    private static function stepThree(): void
    {
        $pages = [
            'about' => [
                'title'            => 'About Us',
                'hero_eyebrow'     => 'ABOUT EXCELBIDS',
                'hero_intro'       => 'We turn technical expertise into compliant, commissioner-ready bids so good organisations win the work they deserve.',
                'meta_title'       => 'About ExcelBids — Bid & Tender Writing Consultancy',
                'meta_description' => 'Learn about ExcelBids, our team of bid writing specialists, our history, values, and how we help UK organisations win tenders.',
                'sort_order'       => 1,
                'sections'         => [
                    [
                        'bg'   => 'paper',
                        'cols' => '1',
                        'blocks' => [
                            [0, 'heading', ['text' => 'Who We Are & What We Do', 'eyebrow' => 'OUR MISSION', 'level' => 'h2', 'align' => 'center']],
                            [0, 'document', [
                                'topline'      => 'EXCELBIDS CONSULTANCY PROFILE',
                                'body'         => 'A specialist bid-writing consultancy for UK organisations. Our approach is built around [m]person-centred outcomes[/m], watertight compliance, and clear scoring strategies.',
                                'note'         => '100% compliant',
                                'stamp'        => 'UK WIDE',
                                'sticky'       => 'GOAL',
                                'sticky_value' => 'Win Rate Excellence',
                            ]],
                        ],
                    ],
                    [
                        'bg'   => 'navy',
                        'cols' => '3',
                        'blocks' => [
                            [0, 'stats', ['style' => 'bar', 'items' => [['value' => '92%', 'label' => 'Average QA score before submission']]]],
                            [1, 'stats', ['style' => 'bar', 'items' => [['value' => '7', 'label' => 'Procurement frameworks won on']]]],
                            [2, 'stats', ['style' => 'bar', 'items' => [['value' => '100%', 'label' => 'Confidentiality & NDA coverage']]]],
                        ],
                    ],
                    [
                        'bg'   => 'white',
                        'cols' => '2',
                        'blocks' => [
                            [0, 'heading', ['text' => 'Our Core Principles', 'level' => 'h3']],
                            [0, 'list', ['marker' => 'tick', 'items' => [
                                ['text' => 'Compliance First', 'note' => 'Every specification requirement matched line by line.'],
                                ['text' => 'High-Quality Prose', 'note' => 'Clear, compelling language designed for evaluators to score quickly.'],
                                ['text' => 'Fixed-Fee Transparency', 'note' => 'Clear upfront pricing with no unexpected costs.'],
                            ]]],
                            [1, 'heading', ['text' => 'What Our Clients Say', 'level' => 'h3']],
                            [1, 'testimonials', ['items' => [
                                ['quote' => 'Spotted a scoring weakness we would have missed entirely.', 'author' => 'Registered Manager', 'org' => 'Domiciliary Care'],
                                ['quote' => 'Full QA process, and we still submitted two days early.', 'author' => 'Operations Director', 'org' => 'Facilities Management'],
                            ]]],
                        ],
                    ],
                    [
                        'bg'   => 'tint',
                        'cols' => '1',
                        'blocks' => [
                            [0, 'cta', [
                                'heading'      => 'Ready to work with our bid writing team?',
                                'text'         => 'Tell us about your upcoming tender opportunity. We respond with a scope and fee proposal within 24 hours.',
                                'button_label' => 'Submit a Consultation Request',
                                'button_url'   => '/consultation',
                                'stamp'        => 'APPROVED',
                            ]],
                        ],
                    ],
                ],
            ],
            'services' => [
                'title'            => 'Our Services',
                'hero_eyebrow'     => 'WHAT WE OFFER',
                'hero_intro'       => 'Full-spectrum tender, bid writing, framework application, and QA review services for UK businesses and charities.',
                'meta_title'       => 'Bid & Tender Writing Services — ExcelBids',
                'meta_description' => 'Explore our full range of bid writing, tender review, framework application, and PQQ/SQ services.',
                'sort_order'       => 2,
                'sections'         => [
                    [
                        'bg'   => 'paper',
                        'cols' => '1',
                        'blocks' => [
                            [0, 'heading', ['text' => 'Comprehensive Tender & Bid Support', 'eyebrow' => 'EXCELBIDS SERVICES', 'level' => 'h2', 'align' => 'center']],
                            [0, 'text', ['body' => '<p>Whether you need a complete bid written from scratch, a strategic review of your draft, or assistance navigating procurement frameworks, our expert writers are here to help.</p>', 'size' => 'large']],
                        ],
                    ],
                    [
                        'bg'   => 'paper',
                        'cols' => '1',
                        'blocks' => [
                            [0, 'cards', ['columns' => '3', 'style' => 'numbered', 'items' => [
                                ['title' => 'Tender & Bid Writing', 'text' => 'Full written responses to public and private sector tenders, tailored to the buyer specification.'],
                                ['title' => 'Bid Reviews', 'text' => 'An independent read and scoring audit of a draft you have already written.'],
                                ['title' => 'Opportunity Search', 'text' => 'Ongoing monitoring of procurement portals for contracts that fit your capability.'],
                                ['title' => 'PQQ / SQ Completion', 'text' => 'Selection questionnaires completed, formatted, and fully evidenced.'],
                                ['title' => 'Framework Applications', 'text' => 'Applications to frameworks and dynamic purchasing systems (DPS).'],
                                ['title' => 'Grant Proposals', 'text' => 'High-scoring funding applications for charities and social enterprises.'],
                            ]]],
                        ],
                    ],
                    [
                        'bg'   => 'navy',
                        'cols' => '1',
                        'blocks' => [
                            [0, 'heading', ['text' => 'Rigorous 4-Stage Quality Assurance', 'eyebrow' => 'QUALITY ASSURANCE', 'level' => 'h2', 'align' => 'center']],
                            [0, 'checklist', [
                                'signature'      => 'Cleared for submission',
                                'signature_meta' => 'QA REVIEWER - EXCELBIDS TEAM',
                                'items' => [
                                    ['title' => 'Formatting & Evidence Audit', 'text' => 'Word counts, layout structure and claim verification.'],
                                    ['title' => 'Compliance Check', 'text' => 'Line-by-line verification against the specification and ITT.'],
                                    ['title' => 'Independent Read-Through', 'text' => 'Read cold by a senior writer acting as an evaluator.'],
                                    ['title' => 'Final Proofread', 'text' => 'Grammar, tone, presentation, and document formatting.'],
                                ],
                            ]],
                        ],
                    ],
                    [
                        'bg'   => 'white',
                        'cols' => '1',
                        'blocks' => [
                            [0, 'heading', ['text' => 'Our 7-Step Bid Writing Process', 'eyebrow' => 'THE PROCESS', 'level' => 'h2', 'align' => 'center']],
                            [0, 'steps', ['items' => [
                                ['title' => '1. Consultation', 'text' => 'We learn your organisation, core strengths, and tender requirements.'],
                                ['title' => '2. Opportunity Review', 'text' => 'We analyze the ITT documents and confirm a go/no-go recommendation.'],
                                ['title' => '3. Strategy Session', 'text' => 'Win themes, evidence gaps, and scoring strategy agreed.'],
                                ['title' => '4. Response Drafting', 'text' => 'We write the responses and coordinate with your team for technical detail.'],
                                ['title' => '5. Compliance & QA', 'text' => 'Four independent checks to ensure maximum score potential.'],
                                ['title' => '6. Final Submission', 'text' => 'Uploaded to the portal, attachments verified, and receipt confirmed.'],
                                ['title' => '7. Post-Submission', 'text' => 'Clarifications managed and evaluator feedback reviewed.'],
                            ]]],
                        ],
                    ],
                    [
                        'bg'   => 'tint',
                        'cols' => '1',
                        'blocks' => [
                            [0, 'cta', [
                                'heading'      => 'Discuss Your Next Tender Opportunity',
                                'text'         => 'Partner with ExcelBids to submit compliant, winning bids.',
                                'button_label' => 'Request a Consultation',
                                'button_url'   => '/consultation',
                            ]],
                        ],
                    ],
                ],
            ],
            'contact' => [
                'title'            => 'Contact Us',
                'hero_eyebrow'     => 'GET IN TOUCH',
                'hero_intro'       => 'Have a question about an upcoming tender or want to discuss working with us? Send us a message.',
                'meta_title'       => 'Contact ExcelBids — Get in Touch',
                'meta_description' => 'Get in touch with the ExcelBids team for tender inquiries, consultation requests, or general questions.',
                'sort_order'       => 3,
                'sections'         => [
                    [
                        'bg'   => 'paper',
                        'cols' => 'wide-narrow',
                        'blocks' => [
                            [0, 'form', [
                                'heading'       => 'Submit an Enquiry',
                                'text'          => 'Fill in the details below and our bid team will get back to you within 24 hours.',
                                'show_org'      => '1',
                                'show_phone'    => '1',
                                'show_service'  => '1',
                                'show_sector'   => '1',
                                'message_label' => 'How can we help with your bid?',
                                'button_label'  => 'Send enquiry',
                                'success'       => 'Thank you — your message has been received. We will be in touch shortly.',
                            ]],
                            [1, 'contact_details', [
                                'style'   => 'panel',
                                'heading' => 'Direct Contact',
                                'text'    => 'Our team is available Monday to Friday to assist with your bid writing needs.',
                                'email'   => 'excelbidsconsult@gmail.com',
                                'phone'   => '',
                                'address' => 'United Kingdom',
                                'hours'   => 'Mon - Fri: 08:30 - 18:00',
                            ]],
                        ],
                    ],
                    [
                        'bg'   => 'white',
                        'cols' => '1',
                        'blocks' => [
                            [0, 'heading', ['text' => 'Frequently Asked Questions', 'eyebrow' => 'QUESTIONS & ANSWERS', 'level' => 'h2', 'align' => 'center']],
                            [0, 'accordion', ['items' => [
                                ['question' => 'What size organisations do you work with?', 'answer' => 'We work primarily with SMEs, care providers, charities, and larger contractors across the UK.'],
                                ['question' => 'How much notice do you need for a bid?', 'answer' => 'The earlier the better, but we frequently accommodate short-turnaround tenders.'],
                                ['question' => 'How are your fees structured?', 'answer' => 'We provide transparent fixed-fee quotes per project or daily rates based on scope and timeline.'],
                                ['question' => 'Can you help with portal uploads?', 'answer' => 'Yes, our bid management service includes full portal administration and submission verification.'],
                            ]]],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($pages as $slug => $def) {
            $existing = \App\Models\Page::findBySlug($slug, false);
            if ($existing !== null) {
                continue;
            }

            $pageId = Database::insert('pages', [
                'title'            => $def['title'],
                'slug'             => $slug,
                'layout_mode'      => 'blocks',
                'show_page_header' => 1,
                'hero_eyebrow'     => $def['hero_eyebrow'],
                'hero_intro'       => $def['hero_intro'],
                'body'             => '',
                'meta_title'       => $def['meta_title'],
                'meta_description' => $def['meta_description'],
                'is_published'     => 1,
                'show_in_footer'   => 1,
                'sort_order'       => $def['sort_order'],
                'created_at'       => date('Y-m-d H:i:s'),
            ]);

            foreach ($def['sections'] as $secDef) {
                $secId = \App\Models\Page::addBlock($pageId, 'section', null, 0, [
                    'background' => $secDef['bg'],
                    'columns'    => $secDef['cols'],
                ]);

                foreach ($secDef['blocks'] as $bDef) {
                    [$colIdx, $type, $settings] = $bDef;
                    \App\Models\Page::addBlock($pageId, $type, $secId, $colIdx, $settings);
                }
            }
        }

        Database::run("UPDATE menu_items SET url = '/about' WHERE url = '/#about'");
        Database::run("UPDATE menu_items SET url = '/services' WHERE url = '/#services'");

        $contactMenuExists = (int) Database::scalar(
            "SELECT COUNT(*) FROM menu_items WHERE location = 'primary' AND url = '/contact'",
            [],
            0
        );
        if ($contactMenuExists === 0) {
            Database::insert('menu_items', [
                'location'   => 'primary',
                'label'      => 'Contact Us',
                'url'        => '/contact',
                'sort_order' => 6,
                'is_active'  => 1,
            ]);
        }
    }

    private static function hasTable(string $table): bool
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$table],
            0
        ) > 0;
    }

    private static function hasColumn(string $table, string $column): bool
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$table, $column],
            0
        ) > 0;
    }
}
