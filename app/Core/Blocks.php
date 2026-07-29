<?php

declare(strict_types=1);

namespace App\Core;

/**
 * The block registry.
 *
 * One definition per block type drives everything: the picker in the builder,
 * the edit form, validation, and which template renders it. Adding a block
 * means adding an entry here and a template in Views/site/blocks.
 *
 * Field types understood by the editor:
 *   text, textarea, richtext, number, url, select, bool, image, colorstyle,
 *   repeater (with a nested `fields` definition)
 */
final class Blocks
{
    /** Sections are the only top-level block; everything else lives inside one. */
    public const SECTION = 'section';

    /** Background treatments, matching the palette used across the site. */
    public const BACKGROUNDS = [
        'paper' => 'Paper (default)',
        'tint'  => 'Tinted band',
        'white' => 'White',
        'navy'  => 'Navy (dark)',
        'navy_glow' => 'Navy with gold glow',
    ];

    public const WIDTHS = [
        'normal' => 'Normal (1120px)',
        'narrow' => 'Narrow (760px)',
        'wide'   => 'Wide (1320px)',
    ];

    public const SPACING = [
        'none'   => 'None',
        'small'  => 'Small',
        'normal' => 'Normal',
        'large'  => 'Large',
    ];

    public const ALIGN = ['left' => 'Left', 'center' => 'Centre', 'right' => 'Right'];

    public const BUTTON_STYLES = [
        'red'         => 'Red (primary)',
        'primary'     => 'Dark',
        'ghost'       => 'Outline',
        'ghost-light' => 'Outline, light (for dark backgrounds)',
    ];

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function types(): array
    {
        return [

            // ---------------------------------------------------------------
            // Layout
            // ---------------------------------------------------------------
            'section' => [
                'label' => 'Section', 'group' => 'Layout', 'icon' => '▤',
                'description' => 'A full-width band that holds everything else. Choose a background and how many columns it has.',
                'container' => true,
                'fields' => [
                    'background'  => ['type' => 'select', 'label' => 'Background', 'options' => self::BACKGROUNDS, 'default' => 'paper'],
                    'width'       => ['type' => 'select', 'label' => 'Content width', 'options' => self::WIDTHS, 'default' => 'normal'],
                    'columns'     => ['type' => 'select', 'label' => 'Columns', 'default' => '1', 'options' => [
                        '1' => 'One column', '2' => 'Two equal', '3' => 'Three equal', '4' => 'Four equal',
                        'wide-narrow' => 'Two: wide + narrow', 'narrow-wide' => 'Two: narrow + wide',
                    ]],
                    'align_items' => ['type' => 'select', 'label' => 'Column alignment', 'default' => 'start', 'options' => [
                        'start' => 'Top', 'center' => 'Middle', 'stretch' => 'Equal height',
                    ]],
                    'padding_top'    => ['type' => 'select', 'label' => 'Space above', 'options' => self::SPACING, 'default' => 'normal'],
                    'padding_bottom' => ['type' => 'select', 'label' => 'Space below', 'options' => self::SPACING, 'default' => 'normal'],
                    'eyebrow'     => ['type' => 'text', 'label' => 'Eyebrow label', 'help' => 'Small red uppercase label above the heading.'],
                    'file_num'    => ['type' => 'text', 'label' => 'File number', 'help' => 'The monospaced "FILE §01" marker used on the home page.'],
                    'heading'     => ['type' => 'text', 'label' => 'Section heading'],
                    'intro'       => ['type' => 'textarea', 'label' => 'Section intro'],
                    'ghost_num'   => ['type' => 'text', 'label' => 'Ghost numeral', 'help' => 'The huge faded number in the corner, e.g. 01. Leave blank to hide.'],
                    'anchor'      => ['type' => 'text', 'label' => 'Anchor ID', 'help' => 'Lets you link straight to this section, e.g. "team" gives /about#team.'],
                ],
            ],

            // ---------------------------------------------------------------
            // Content
            // ---------------------------------------------------------------
            'heading' => [
                'label' => 'Heading', 'group' => 'Content', 'icon' => 'H',
                'description' => 'A standalone heading.',
                'fields' => [
                    'text'    => ['type' => 'text', 'label' => 'Heading text', 'required' => true],
                    'level'   => ['type' => 'select', 'label' => 'Size', 'default' => 'h2', 'options' => [
                        'h2' => 'Large (h2)', 'h3' => 'Medium (h3)', 'h4' => 'Small (h4)',
                    ]],
                    'eyebrow' => ['type' => 'text', 'label' => 'Eyebrow label'],
                    'align'   => ['type' => 'select', 'label' => 'Alignment', 'options' => self::ALIGN, 'default' => 'left'],
                ],
            ],

            'text' => [
                'label' => 'Text', 'group' => 'Content', 'icon' => '¶',
                'description' => 'Formatted text with headings, lists and links.',
                'fields' => [
                    'body'  => ['type' => 'richtext', 'label' => 'Content', 'required' => true],
                    'size'  => ['type' => 'select', 'label' => 'Text size', 'default' => 'normal', 'options' => [
                        'normal' => 'Normal', 'large' => 'Large (intro paragraph)', 'small' => 'Small',
                    ]],
                ],
            ],

            'image' => [
                'label' => 'Image', 'group' => 'Content', 'icon' => '❑',
                'description' => 'A picture from your media library.',
                'fields' => [
                    'media_id' => ['type' => 'image', 'label' => 'Image', 'required' => true],
                    'alt'      => ['type' => 'text', 'label' => 'Alt text', 'help' => 'Describes the image for screen readers and search engines.'],
                    'caption'  => ['type' => 'text', 'label' => 'Caption'],
                    'style'    => ['type' => 'select', 'label' => 'Style', 'default' => 'plain', 'options' => [
                        'plain' => 'Plain', 'framed' => 'Framed', 'shadow' => 'Drop shadow', 'paper' => 'Paper document (tilted)',
                    ]],
                    'align'    => ['type' => 'select', 'label' => 'Alignment', 'options' => self::ALIGN, 'default' => 'left'],
                    'max_width' => ['type' => 'number', 'label' => 'Maximum width (px)', 'help' => 'Leave blank to fill the column.'],
                ],
            ],

            'buttons' => [
                'label' => 'Buttons', 'group' => 'Content', 'icon' => '▭',
                'description' => 'One or more call-to-action buttons.',
                'fields' => [
                    'align' => ['type' => 'select', 'label' => 'Alignment', 'options' => self::ALIGN, 'default' => 'left'],
                    'items' => ['type' => 'repeater', 'label' => 'Buttons', 'min_rows' => 1, 'fields' => [
                        'label' => ['type' => 'text', 'label' => 'Label', 'primary' => true],
                        'url'   => ['type' => 'text', 'label' => 'Link'],
                        'style' => ['type' => 'select', 'label' => 'Style', 'options' => self::BUTTON_STYLES, 'default' => 'red'],
                    ]],
                ],
            ],

            'list' => [
                'label' => 'Tick list', 'group' => 'Content', 'icon' => '✓',
                'description' => 'A list of points, each with a tick or number.',
                'fields' => [
                    'marker' => ['type' => 'select', 'label' => 'Marker', 'default' => 'tick', 'options' => [
                        'tick' => 'Tick', 'number' => 'Number', 'dash' => 'Dash',
                    ]],
                    'items' => ['type' => 'repeater', 'label' => 'Items', 'fields' => [
                        'text' => ['type' => 'text', 'label' => 'Item', 'primary' => true],
                        'note' => ['type' => 'text', 'label' => 'Supporting note'],
                    ]],
                ],
            ],

            'divider' => [
                'label' => 'Divider', 'group' => 'Layout', 'icon' => '—',
                'description' => 'A horizontal rule.',
                'fields' => [
                    'style' => ['type' => 'select', 'label' => 'Style', 'default' => 'line', 'options' => [
                        'line' => 'Solid line', 'dashed' => 'Dashed', 'space' => 'Invisible',
                    ]],
                ],
            ],

            'spacer' => [
                'label' => 'Spacer', 'group' => 'Layout', 'icon' => '↕',
                'description' => 'Vertical breathing room.',
                'fields' => [
                    'height' => ['type' => 'select', 'label' => 'Height', 'options' => self::SPACING, 'default' => 'normal'],
                ],
            ],

            // ---------------------------------------------------------------
            // Design components, in the ExcelBids visual language
            // ---------------------------------------------------------------
            'cards' => [
                'label' => 'Card grid', 'group' => 'Components', 'icon' => '⊞',
                'description' => 'A grid of numbered or icon cards — the treatment used for Services on the home page.',
                'fields' => [
                    'columns' => ['type' => 'select', 'label' => 'Cards per row', 'default' => '3', 'options' => [
                        '2' => 'Two', '3' => 'Three', '4' => 'Four',
                    ]],
                    'style' => ['type' => 'select', 'label' => 'Card style', 'default' => 'numbered', 'options' => [
                        'numbered' => 'Numbered tile (red index)',
                        'seal'     => 'Gold seal (for dark backgrounds)',
                        'plain'    => 'Plain card',
                    ]],
                    'items' => ['type' => 'repeater', 'label' => 'Cards', 'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Title', 'primary' => true],
                        'text'  => ['type' => 'textarea', 'label' => 'Description'],
                        'icon'  => ['type' => 'text', 'label' => 'Icon character', 'help' => 'Used by the seal style, e.g. ✓ ✎ ◈ £'],
                        'url'   => ['type' => 'text', 'label' => 'Link (optional)'],
                    ]],
                ],
            ],

            'stats' => [
                'label' => 'Statistics', 'group' => 'Components', 'icon' => '◔',
                'description' => 'Big figures with captions, like the band under the home page hero.',
                'fields' => [
                    'style' => ['type' => 'select', 'label' => 'Style', 'default' => 'bar', 'options' => [
                        'bar'   => 'Gold-ruled (for dark backgrounds)',
                        'plain' => 'Plain (for light backgrounds)',
                    ]],
                    'items' => ['type' => 'repeater', 'label' => 'Figures', 'fields' => [
                        'value' => ['type' => 'text', 'label' => 'Figure', 'primary' => true, 'help' => 'e.g. 92%, £1.4M'],
                        'label' => ['type' => 'text', 'label' => 'Caption'],
                    ]],
                    'note' => ['type' => 'text', 'label' => 'Footnote'],
                ],
            ],

            'steps' => [
                'label' => 'Numbered steps', 'group' => 'Components', 'icon' => '◈',
                'description' => 'The process stepper used on the home page.',
                'fields' => [
                    'items' => ['type' => 'repeater', 'label' => 'Steps', 'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Step title', 'primary' => true],
                        'text'  => ['type' => 'textarea', 'label' => 'Description'],
                    ]],
                ],
            ],

            'accordion' => [
                'label' => 'Accordion / FAQ', 'group' => 'Interactive', 'icon' => '⊟',
                'description' => 'Expandable questions and answers.',
                'fields' => [
                    'items' => ['type' => 'repeater', 'label' => 'Questions', 'fields' => [
                        'question' => ['type' => 'text', 'label' => 'Question', 'primary' => true],
                        'answer'   => ['type' => 'textarea', 'label' => 'Answer'],
                    ]],
                ],
            ],

            'testimonials' => [
                'label' => 'Testimonials', 'group' => 'Components', 'icon' => '❝',
                'description' => 'Tilted quote cards.',
                'fields' => [
                    'items' => ['type' => 'repeater', 'label' => 'Quotes', 'fields' => [
                        'quote'  => ['type' => 'textarea', 'label' => 'Quote', 'primary' => true],
                        'author' => ['type' => 'text', 'label' => 'Job title'],
                        'org'    => ['type' => 'text', 'label' => 'Sector or organisation'],
                    ]],
                ],
            ],

            'checklist' => [
                'label' => 'Sign-off sheet', 'group' => 'Components', 'icon' => '☑',
                'description' => 'The QA sign-off panel with hand-drawn ticks and a signature line.',
                'fields' => [
                    'items' => ['type' => 'repeater', 'label' => 'Checks', 'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Check', 'primary' => true],
                        'text'  => ['type' => 'text', 'label' => 'Description'],
                    ]],
                    'signature'      => ['type' => 'text', 'label' => 'Signature line', 'default' => 'Cleared for submission'],
                    'signature_meta' => ['type' => 'text', 'label' => 'Signature detail'],
                ],
            ],

            'document' => [
                'label' => 'Paper document', 'group' => 'Components', 'icon' => '📄',
                'description' => 'The tilted case-file panel from the hero, with a stamp and a handwritten note.',
                'fields' => [
                    'topline'   => ['type' => 'text', 'label' => 'Header line', 'help' => 'Monospaced, e.g. TENDER RESPONSE — DRAFT 3 OF 3'],
                    'body'      => ['type' => 'textarea', 'label' => 'Document text', 'help' => 'Wrap words in [m]...[/m] to highlight them.'],
                    'note'      => ['type' => 'text', 'label' => 'Handwritten note'],
                    'stamp'     => ['type' => 'text', 'label' => 'Stamp text', 'help' => 'Two short words work best, separated by a comma.'],
                    'sticky'       => ['type' => 'text', 'label' => 'Sticky note label'],
                    'sticky_value' => ['type' => 'text', 'label' => 'Sticky note value'],
                ],
            ],

            'tags' => [
                'label' => 'Tag pills', 'group' => 'Components', 'icon' => '◍',
                'description' => 'Rounded pills, used for sectors on the home page.',
                'fields' => [
                    'items' => ['type' => 'repeater', 'label' => 'Tags', 'fields' => [
                        'label'   => ['type' => 'text', 'label' => 'Tag', 'primary' => true],
                        'is_core' => ['type' => 'bool', 'label' => 'Emphasised (dark fill)'],
                    ]],
                ],
            ],

            'cta' => [
                'label' => 'Call to action', 'group' => 'Components', 'icon' => '◉',
                'description' => 'The navy panel with the APPROVED stamp.',
                'fields' => [
                    'heading'      => ['type' => 'text', 'label' => 'Heading', 'required' => true],
                    'text'         => ['type' => 'textarea', 'label' => 'Supporting text'],
                    'note'         => ['type' => 'text', 'label' => 'Handwritten note'],
                    'button_label' => ['type' => 'text', 'label' => 'Button label'],
                    'button_url'   => ['type' => 'text', 'label' => 'Button link'],
                    'button2_label' => ['type' => 'text', 'label' => 'Second button label'],
                    'button2_url'  => ['type' => 'text', 'label' => 'Second button link'],
                    'stamp'        => ['type' => 'text', 'label' => 'Stamp text', 'help' => 'Comma separated, e.g. APPROVED, FOR, SUBMISSION'],
                    'email'        => ['type' => 'text', 'label' => 'Email shown under the text'],
                ],
            ],

            'contact_details' => [
                'label' => 'Contact details', 'group' => 'Components', 'icon' => '✆',
                'description' => 'Email, phone, address and hours, pulled from Settings unless overridden.',
                'fields' => [
                    'style'   => ['type' => 'select', 'label' => 'Style', 'default' => 'panel', 'options' => [
                        'panel' => 'Navy panel', 'plain' => 'Plain list',
                    ]],
                    'heading' => ['type' => 'text', 'label' => 'Heading'],
                    'text'    => ['type' => 'textarea', 'label' => 'Intro'],
                    'email'   => ['type' => 'text', 'label' => 'Email', 'help' => 'Leave blank to use the address from Settings.'],
                    'phone'   => ['type' => 'text', 'label' => 'Phone', 'help' => 'Leave blank to use the number from Settings.'],
                    'address' => ['type' => 'textarea', 'label' => 'Address'],
                    'hours'   => ['type' => 'text', 'label' => 'Opening hours'],
                ],
            ],

            // ---------------------------------------------------------------
            // Interactive
            // ---------------------------------------------------------------
            'form' => [
                'label' => 'Enquiry form', 'group' => 'Interactive', 'icon' => '✉',
                'description' => 'A working contact form. Submissions land in Consultation Requests and are emailed to you.',
                'fields' => [
                    'heading'      => ['type' => 'text', 'label' => 'Form heading'],
                    'text'         => ['type' => 'textarea', 'label' => 'Intro text'],
                    'show_org'     => ['type' => 'bool', 'label' => 'Ask for organisation', 'default' => '1'],
                    'show_phone'   => ['type' => 'bool', 'label' => 'Ask for phone number', 'default' => '1'],
                    'show_service' => ['type' => 'bool', 'label' => 'Ask which service they need', 'default' => '1'],
                    'show_sector'  => ['type' => 'bool', 'label' => 'Ask for their sector', 'default' => '0'],
                    'show_deadline' => ['type' => 'bool', 'label' => 'Ask for their deadline', 'default' => '0'],
                    'message_label' => ['type' => 'text', 'label' => 'Message field label', 'default' => 'How can we help?'],
                    'button_label' => ['type' => 'text', 'label' => 'Button label', 'default' => 'Send enquiry'],
                    'success'      => ['type' => 'textarea', 'label' => 'Thank-you message',
                                       'default' => 'Thank you — your message has been received. We will be in touch shortly.'],
                ],
            ],

            'embed' => [
                'label' => 'Video or map', 'group' => 'Interactive', 'icon' => '▶',
                'description' => 'Embed a YouTube or Vimeo video, or a Google Map.',
                'fields' => [
                    'url'    => ['type' => 'text', 'label' => 'Address', 'required' => true,
                                 'help' => 'Paste a YouTube, Vimeo or Google Maps link.'],
                    'ratio'  => ['type' => 'select', 'label' => 'Shape', 'default' => '16x9', 'options' => [
                        '16x9' => 'Widescreen (16:9)', '4x3' => 'Classic (4:3)', 'square' => 'Square',
                    ]],
                    'title'  => ['type' => 'text', 'label' => 'Accessible title', 'help' => 'Describes the embed for screen readers.'],
                ],
            ],

            'html' => [
                'label' => 'Custom HTML', 'group' => 'Interactive', 'icon' => '</>',
                'description' => 'Hand-written HTML, for anything the other blocks do not cover.',
                'fields' => [
                    'code' => ['type' => 'textarea', 'label' => 'HTML', 'rows' => 10,
                               'help' => 'A safe subset is allowed. Scripts, styles and iframes are removed when you save.'],
                ],
            ],
        ];
    }

    /** @return array<string,mixed>|null */
    public static function definition(string $type): ?array
    {
        return self::types()[$type] ?? null;
    }

    public static function exists(string $type): bool
    {
        return isset(self::types()[$type]);
    }

    public static function label(string $type): string
    {
        return (string) (self::definition($type)['label'] ?? labelize($type));
    }

    public static function icon(string $type): string
    {
        return (string) (self::definition($type)['icon'] ?? '▦');
    }

    /**
     * Block types available inside a section, grouped for the picker.
     *
     * @return array<string,array<string,array<string,mixed>>>
     */
    public static function pickerGroups(): array
    {
        $groups = [];
        foreach (self::types() as $type => $definition) {
            if ($type === self::SECTION) {
                continue;
            }
            $groups[$definition['group']][$type] = $definition;
        }
        return $groups;
    }

    /** How many columns a section layout produces. */
    public static function columnCount(string $layout): int
    {
        return match ($layout) {
            '2', 'wide-narrow', 'narrow-wide' => 2,
            '3' => 3,
            '4' => 4,
            default => 1,
        };
    }

    /**
     * Defaults for a newly added block, so it renders sensibly straight away.
     *
     * @return array<string,mixed>
     */
    public static function defaults(string $type): array
    {
        $definition = self::definition($type);
        if ($definition === null) {
            return [];
        }

        $settings = [];
        foreach ($definition['fields'] as $name => $field) {
            if ($field['type'] === 'repeater') {
                $settings[$name] = [];
                continue;
            }
            $settings[$name] = (string) ($field['default'] ?? '');
        }

        return $settings;
    }
}
