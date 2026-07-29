-- ExcelBids — seed content.
-- Populates the CMS with the copy from the approved design so the public site
-- renders identically on first boot. Import AFTER schema.sql.
-- The admin account is created by the web installer, not here.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- System settings
-- ---------------------------------------------------------------------------

INSERT INTO `settings` (`key`, `value`, `group_name`, `type`, `label`, `hint`, `sort_order`, `updated_at`) VALUES
('site_name',            'ExcelBids',                          'general', 'text',     'Site name',              'Used in the browser title and emails.', 1, NOW()),
('site_tagline',         'Tender, Bid & Grant Writing Specialists', 'general', 'text', 'Tagline',              'Appears after the site name in the page title.', 2, NOW()),
('contact_email',        'excelbidsconsult@gmail.com',         'general', 'text',     'Public contact email',   'Shown on the site and used as the reply-to for enquiries.', 3, NOW()),
('contact_phone',        '',                                   'general', 'text',     'Public phone number',    'Leave blank to hide.', 4, NOW()),
('contact_location',     'United Kingdom',                     'general', 'text',     'Location',               '', 5, NOW()),
('notify_email',         'excelbidsconsult@gmail.com',         'general', 'text',     'Enquiry notification inbox', 'New consultation requests are emailed here.', 6, NOW()),
('logo_image',       '', 'branding', 'image',  'Logo',                       'Shown in the website header and in emails. A wide (landscape) PNG or SVG works best. Leave empty to use the ExcelBids wordmark.', 1, NOW()),
('logo_image_dark',  '', 'branding', 'image',  'Logo for dark backgrounds',  'Optional. The admin panel and client portal sidebars are dark navy - upload a white or light version here if your main logo would disappear against them.', 2, NOW()),
('logo_height',      '34', 'branding', 'number','Logo height (pixels)',      'How tall the logo appears in the website header. The width adjusts automatically. Between 20 and 90.', 3, NOW()),
('favicon_image',    '', 'branding', 'image',  'Favicon',                    'The small icon in the browser tab. A square PNG of at least 180x180 is ideal - it is also used as the icon when someone saves the site to a phone home screen.', 4, NOW()),
('og_image',         '', 'branding', 'image',  'Social sharing image',       'Shown when a link to your site is posted on LinkedIn, X or WhatsApp. Landscape, ideally 1200x630.', 5, NOW()),
('meta_description',     'A specialist bid-writing consultancy for UK organisations - compliant, commissioner-ready tender, bid and grant submissions.', 'seo', 'textarea', 'Default meta description', 'Up to 160 characters.', 1, NOW()),
('meta_keywords',        'bid writing, tender writing, grant writing, PQQ, SQ, framework applications, UK',  'seo', 'text', 'Meta keywords', 'Optional.', 2, NOW()),
('google_analytics_id',  '',                                   'seo', 'text',         'Google Analytics ID',    'e.g. G-XXXXXXXXXX. Leave blank to disable.', 3, NOW()),
('mail_transport',       'mail',                               'mail', 'select',      'Mail transport',         'mail = PHP mail() (works on most cPanel hosts). smtp = authenticated SMTP.', 1, NOW()),
('mail_from_email',      'excelbidsconsult@gmail.com',         'mail', 'text',        'From address',           'Should be a mailbox on your own domain for best deliverability.', 2, NOW()),
('mail_from_name',       'ExcelBids',                          'mail', 'text',        'From name',              '', 3, NOW()),
('smtp_host',            '',                                   'mail', 'text',        'SMTP host',              'e.g. mail.yourdomain.co.uk', 4, NOW()),
('smtp_port',            '587',                                'mail', 'number',      'SMTP port',              '587 for TLS, 465 for SSL.', 5, NOW()),
('smtp_secure',          'tls',                                'mail', 'select',      'SMTP encryption',        'tls, ssl or none.', 6, NOW()),
('smtp_username',        '',                                   'mail', 'text',        'SMTP username',          '', 7, NOW()),
('smtp_password',        '',                                   'mail', 'password',    'SMTP password',          'Stored in the database. Leave blank to keep the current value.', 8, NOW()),
('enquiry_autoreply',    '1',                                  'mail', 'bool',        'Send auto-reply to enquirers', 'Confirms receipt of a consultation request.', 9, NOW()),
('portal_enabled',       '1',                                  'portal', 'bool',      'Enable client portal',   'Turns the client login on or off site-wide.', 1, NOW()),
('portal_uploads',       '1',                                  'portal', 'bool',      'Allow client uploads',   'Clients can attach documents to their bids.', 2, NOW()),
('portal_messaging',     '1',                                  'portal', 'bool',      'Enable portal messaging','Two-way messages between clients and the bid team.', 3, NOW()),
('upload_max_mb',        '10',                                 'portal', 'number',    'Max upload size (MB)',   'Must not exceed your hosting upload_max_filesize.', 4, NOW()),
('currency_symbol',      '£',                                  'general', 'text',     'Currency symbol',        '', 7, NOW()),
('bid_ref_prefix',       'EB',                                 'general', 'text',     'Bid reference prefix',   'Bids are numbered PREFIX/YYYY/0001.', 8, NOW())
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `hint` = VALUES(`hint`), `group_name` = VALUES(`group_name`);

-- ---------------------------------------------------------------------------
-- Home page section visibility and order
-- ---------------------------------------------------------------------------

INSERT INTO `page_sections` (`section_key`, `title`, `is_visible`, `sort_order`) VALUES
('hero',        'Hero',                    1, 1),
('portals',     'Portals strip',           1, 2),
('proof_strip', 'Statistics bar',          1, 3),
('about',       'About (File 01)',         1, 4),
('services',    'Services (File 02)',      1, 5),
('sectors',     'Sectors (File 03)',       1, 6),
('process',     'Process (File 04)',       1, 7),
('qa',          'QA sign-off (File 05)',   1, 8),
('why',         'Why choose us (File 06)', 1, 9),
('proof',       'Proof of work (File 07)', 1, 10),
('faq',         'FAQs (File 08)',          1, 11),
('cta',         'Closing call to action',  1, 12)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- ---------------------------------------------------------------------------
-- Editable copy, grouped by section
-- ---------------------------------------------------------------------------

INSERT INTO `content_blocks` (`key`, `section`, `label`, `hint`, `type`, `value`, `sort_order`, `updated_at`) VALUES
-- Header
('header_strip_left',    'header', 'Top strip - left text',   '', 'text', 'Questions before you start?', 1, NOW()),
('header_strip_right',   'header', 'Top strip - right text',  '', 'text', 'Existing client?', 2, NOW()),
('nav_cta_label',        'header', 'Header button label',     '', 'text', 'Start a Consultation', 3, NOW()),

-- Hero
('hero_case_tag',        'hero', 'Case file tag',      'The small monospaced label above the headline.', 'text', 'MORE BIDS. BETTER BIDS.', 1, NOW()),
('hero_heading',         'hero', 'Headline',           'Wrap a word in [c]...[/c] to give it the brand highlight.', 'text', 'We help you prepare [c]winning[/c] bids that deliver', 2, NOW()),
('hero_lead',            'hero', 'Intro paragraph',    '', 'textarea', 'Expert bid writing services that help businesses win more opportunities and grow with confidence.', 3, NOW()),
('hero_btn_primary',     'hero', 'Primary button',     '', 'text', 'Our Services →', 4, NOW()),
('hero_btn_secondary',   'hero', 'Secondary button',   '', 'text', 'How We Work →', 5, NOW()),
('hero_trust_1_value',   'hero', 'Trust stat 1 - value', '', 'text', '8', 6, NOW()),
('hero_trust_1_label',   'hero', 'Trust stat 1 - label', '', 'text', 'Portals covered', 7, NOW()),
('hero_trust_2_value',   'hero', 'Trust stat 2 - value', '', 'text', 'NDA', 8, NOW()),
('hero_trust_2_label',   'hero', 'Trust stat 2 - label', '', 'text', 'Every engagement', 9, NOW()),
('hero_trust_3_value',   'hero', 'Trust stat 3 - value', '', 'text', '4-Stage', 10, NOW()),
('hero_trust_3_label',   'hero', 'Trust stat 3 - label', '', 'text', 'QA process', 11, NOW()),
('hero_doc_topline',     'hero', 'Document - header line', '', 'text', 'TENDER RESPONSE - DRAFT 3 OF 3', 12, NOW()),
('hero_doc_para_1',      'hero', 'Document - paragraph 1', 'Wrap text in [m]...[/m] to highlight it.', 'textarea', 'Our approach is built around [m]person-centred outcomes[/m], evidenced against CQC''s five key questions.', 13, NOW()),
('hero_doc_para_2',      'hero', 'Document - paragraph 2', '', 'textarea', 'Staff are vetted and trained against a competency matrix reviewed quarterly.', 14, NOW()),
('hero_doc_note',        'hero', 'Document - handwritten note', '', 'text', 'strong evidence', 15, NOW()),
('hero_doc_stamp',       'hero', 'Document - stamp text', 'Use a line break for two lines.', 'textarea', 'PASS\nCOMPLIANT', 16, NOW()),
('hero_sticky_label',    'hero', 'Sticky note - label',  '', 'text', 'DEADLINE', 17, NOW()),
('hero_sticky_value',    'hero', 'Sticky note - value',  '', 'text', 'Fri 14 Aug - 12:00', 18, NOW()),

-- Portals strip
('portals_label',        'portals', 'Strip label', '', 'text', 'Portals we work in daily', 1, NOW()),

-- Statistics bar
('proof_strip_note',     'proof_strip', 'Footnote', 'Clear this once your real figures are in.', 'text', 'Placeholder figures - swap in your verified numbers before launch.', 1, NOW()),

-- About
('about_file_num',       'about', 'File number',  '', 'text', 'FILE 01', 1, NOW()),
('about_heading',        'about', 'Heading',      '', 'text', 'About ExcelBids', 2, NOW()),
('about_body',           'about', 'Body copy',    '', 'textarea', 'We turn your technical expertise into compliant, commissioner-ready bids - so good organisations win the work they''re capable of delivering.', 3, NOW()),
('about_fact_1_value',   'about', 'Fact 1 - value', '', 'text', 'UK-Wide', 4, NOW()),
('about_fact_1_label',   'about', 'Fact 1 - label', '', 'text', 'Public & private sector', 5, NOW()),
('about_fact_2_value',   'about', 'Fact 2 - value', '', 'text', 'NDA', 6, NOW()),
('about_fact_2_label',   'about', 'Fact 2 - label', '', 'text', 'Confidential by default', 7, NOW()),
('about_fact_3_value',   'about', 'Fact 3 - value', '', 'text', 'Fixed-Fee', 8, NOW()),
('about_fact_3_label',   'about', 'Fact 3 - label', '', 'text', 'Or day-rate, your choice', 9, NOW()),

-- Services
('services_file_num',    'services', 'File number', '', 'text', 'FILE 02', 1, NOW()),
('services_heading',     'services', 'Heading',     '', 'text', 'Our Services', 2, NOW()),
('services_intro',       'services', 'Intro',       'Optional.', 'textarea', '', 3, NOW()),

-- Sectors
('sectors_file_num',     'sectors', 'File number',  '', 'text', 'FILE 03', 1, NOW()),
('sectors_heading',      'sectors', 'Heading',      '', 'text', 'Deepest in health & social care.', 2, NOW()),
('sectors_body',         'sectors', 'Body copy',    '', 'textarea', 'CQC compliance, safeguarding and person-centred outcomes sit at the heart of every bid we write.', 3, NOW()),
('sectors_card_flag',    'sectors', 'Card flag',    'The red tab on the navy card.', 'text', 'SECTOR FOCUS', 4, NOW()),
('sectors_card_title',   'sectors', 'Card title',   '', 'text', 'Health & Social Care', 5, NOW()),
('sectors_card_body',    'sectors', 'Card body',    'Optional.', 'textarea', '', 6, NOW()),
('sectors_card_keywords','sectors', 'Card keywords','Comma separated.', 'text', 'CQC compliance, Safeguarding, DPS applications, LA frameworks', 7, NOW()),

-- Process
('process_file_num',     'process', 'File number', '', 'text', 'FILE 04', 1, NOW()),
('process_heading',      'process', 'Heading',     '', 'text', 'Our Bid Writing Process', 2, NOW()),
('process_intro',        'process', 'Intro',       'Optional.', 'textarea', '', 3, NOW()),

-- QA
('qa_file_num',          'qa', 'File number',  '', 'text', 'FILE 05', 1, NOW()),
('qa_heading',           'qa', 'Heading',      '', 'text', 'Quality Assurance Sign-Off', 2, NOW()),
('qa_signature',         'qa', 'Signature line', 'Rendered in the handwriting font.', 'text', 'Cleared for submission', 3, NOW()),
('qa_signature_meta',    'qa', 'Signature meta', 'Use a line break for two lines.', 'textarea', 'QA REVIEWER - EB TEAM\nDATE: __ / __ / 2026', 4, NOW()),

-- Why
('why_file_num',         'why', 'File number', '', 'text', 'FILE 06', 1, NOW()),
('why_heading',          'why', 'Heading',     '', 'text', 'Why Choose Excel Bids', 2, NOW()),

-- Proof of work
('proof_file_num',       'proof', 'File number', '', 'text', 'FILE 07', 1, NOW()),
('proof_heading',        'proof', 'Heading',     '', 'text', 'Proof of Work', 2, NOW()),
('proof_testimonial_note','proof', 'Testimonials footnote', 'Optional.', 'text', '', 3, NOW()),

-- FAQ
('faq_file_num',         'faq', 'File number', '', 'text', 'FILE 08', 1, NOW()),
('faq_heading',          'faq', 'Heading',     '', 'text', 'Frequently Asked Questions', 2, NOW()),

-- Closing CTA
('cta_heading',          'cta', 'Heading',        '', 'text', 'Every tender you don''t pursue is handed to a competitor.', 1, NOW()),
('cta_sub',              'cta', 'Subheading',     '', 'textarea', 'Expert writing, watertight compliance, a strategy built to score.', 2, NOW()),
('cta_note',             'cta', 'Handwritten note', '', 'text', 'start here', 3, NOW()),
('cta_btn_primary',      'cta', 'Primary button', '', 'text', 'Submit a Consultation Request', 4, NOW()),
('cta_btn_secondary',    'cta', 'Secondary button', '', 'text', 'Log in to Client Portal', 5, NOW()),
('cta_stamp',            'cta', 'Stamp text',     'Use line breaks between words.', 'textarea', 'APPROVED\nFOR\nSUBMISSION', 6, NOW()),

-- Footer
('footer_blurb',         'footer', 'Footer blurb', '', 'textarea', 'Tender, bid & grant writing consultancy for UK organisations.', 1, NOW()),
('footer_copyright',     'footer', 'Copyright line', 'The year is inserted automatically if you use {year}.', 'text', '(c) {year} ExcelBids. All rights reserved.', 2, NOW()),

-- Consultation request form
('quote_heading',        'quote', 'Form page heading', '', 'text', 'Submit a Consultation Request', 1, NOW()),
('quote_intro',          'quote', 'Form page intro',   '', 'textarea', 'Tell us about the opportunity. We will come back to you with a scope, a fee and a delivery plan - usually within one working day.', 2, NOW()),
('quote_success',        'quote', 'Thank-you message', 'Shown after a successful submission.', 'textarea', 'Thank you - your consultation request has been received. We will be in touch shortly using the details you provided.', 3, NOW())
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `hint` = VALUES(`hint`), `section` = VALUES(`section`), `type` = VALUES(`type`);

-- ---------------------------------------------------------------------------
-- Repeatable CMS content
-- ---------------------------------------------------------------------------

INSERT INTO `services` (`title`, `description`, `sort_order`, `is_active`) VALUES
('Tender & Bid Writing',    'Full written responses to public and private sector tenders.', 1, 1),
('Bid Reviews',             'An independent read of a draft you have already written.', 2, 1),
('Opportunity Search',      'Ongoing monitoring of portals for contracts that fit you.', 3, 1),
('Portal Registration',     'Getting you set up and verified on the right procurement portals.', 4, 1),
('PQQ / SQ Completion',     'Selection questionnaires completed and evidenced.', 5, 1),
('Framework Applications',  'Applications to frameworks and dynamic purchasing systems.', 6, 1),
('Grant Proposals',         'Funding applications for charities and social enterprises.', 7, 1),
('Bid Management',          'End-to-end management of the bid programme and its deadlines.', 8, 1),
('Compliance Reviews',      'A line-by-line check against the specification and ITT.', 9, 1),
('Bid Strategy',            'Win themes, scoring strategy and go / no-go decisions.', 10, 1),
('Social Value Responses',  'TOMs-aligned social value answers that actually score.', 11, 1),
('Policies & Documents',    'The supporting policies buyers ask you to attach.', 12, 1);

INSERT INTO `sectors` (`name`, `is_core`, `sort_order`, `is_active`) VALUES
('Health & Social Care', 1, 1, 1),
('Supported Living',     1, 2, 1),
('Home Care',            1, 3, 1),
('NHS',                  0, 4, 1),
('Cleaning',             0, 5, 1),
('Recruitment',          0, 6, 1),
('Construction',         0, 7, 1),
('FM',                   0, 8, 1),
('Education',            0, 9, 1),
('Transport',            0, 10, 1);

INSERT INTO `process_steps` (`title`, `description`, `sort_order`, `is_active`) VALUES
('Consultation',        'We learn your organisation, capability and appetite.', 1, 1),
('Opportunity Review',  'We read the ITT and confirm the bid is worth pursuing.', 2, 1),
('Strategy Session',    'Win themes, evidence gaps and scoring strategy agreed.', 3, 1),
('Drafting',            'We write the response and come back with questions.', 4, 1),
('Compliance & QA',     'Four independent checks against the specification.', 5, 1),
('Submission',          'Uploaded, attachments verified, receipt confirmed.', 6, 1),
('Post-Submission',     'Clarifications handled, feedback captured for next time.', 7, 1);

INSERT INTO `qa_checklist` (`check_key`, `title`, `description`, `sort_order`, `is_active`) VALUES
('formatting',  'Formatting & Evidence Audit', 'Word counts, structure and every claim evidenced.', 1, 1),
('compliance',  'Compliance Check',            'Line-by-line against the specification and ITT.', 2, 1),
('independent', 'Independent Read-Through',    'A second writer reads it cold, as an evaluator would.', 3, 1),
('proofread',   'Final Proofread',             'Language, consistency and presentation.', 4, 1);

INSERT INTO `why_cards` (`seal`, `title`, `description`, `sort_order`, `is_active`) VALUES
('✓', 'Compliance-Focused',          'Nothing gets submitted that could be ruled non-compliant.', 1, 1),
('✎', 'High-Quality Writing',        'Clear, evidenced prose an evaluator can score quickly.', 2, 1),
('◈', 'Strategic Approach',          'We bid to win, not just to respond.', 3, 1),
('£', 'Competitive Pricing',         'Fixed-fee or day-rate, agreed before we start.', 4, 1),
('⏱', 'Deadline Management',         'Submitted early, never at the last minute.', 5, 1),
('◐', 'Client-Focused',              'Your voice and your evidence, sharpened.', 6, 1),
('▤', 'Procurement Knowledge',       'We know how buyers read and score.', 7, 1),
('⚑', 'UK Public Sector Expertise',  'Councils, NHS trusts, housing and central government.', 8, 1);

INSERT INTO `stats` (`value`, `label`, `sort_order`, `is_active`) VALUES
('92%',  'Average QA score before submission', 1, 1),
('7',    'Procurement frameworks won on',      2, 1),
('£—M',  'Contract value secured for clients', 3, 1),
('4',    'Independent QA checks, every bid',   4, 1);

INSERT INTO `portals` (`name`, `line_two`, `sort_order`, `is_active`) VALUES
('PRO',      'CONTRACT',  1, 1),
('YOR',      'TENDER',    2, 1),
('ATAMIS',   '',          3, 1),
('JAGGAER',  '',          4, 1),
('ORACLE',   'SOURCING',  5, 1),
('DELTA',    'eSOURCING', 6, 1),
('IN-TEND',  '',          7, 1),
('SUPPLIER', 'LIVE',      8, 1);

INSERT INTO `faqs` (`question`, `answer`, `sort_order`, `is_active`) VALUES
('What size organisations do you work with?', 'Mostly SMEs, care providers and charities - plus larger organisations needing extra bid-writing capacity.', 1, 1),
('How much notice do you need?',              'More is better, but we regularly support short-turnaround bids. Contact us as soon as you spot an opportunity.', 2, 1),
('How do you charge?',                        'Agreed upfront per project - fixed-fee or day-rate, based on scope and deadline.', 3, 1),
('Do you guarantee we''ll win?',              'No consultancy can guarantee a win. We guarantee a compliant, well-evidenced submission that gives you the strongest chance.', 4, 1);

INSERT INTO `testimonials` (`quote`, `author_role`, `author_org`, `sort_order`, `is_active`) VALUES
('Spotted a scoring weakness we''d have missed entirely.', 'Registered Manager', 'Domiciliary Care', 1, 1),
('Full QA process, and we still submitted two days early.', 'Operations Director', 'Facilities Management', 2, 1),
('Read better than anything we''d written in-house.', 'Bid Lead', 'Supported Living', 3, 1);

INSERT INTO `case_studies` (`eyebrow`, `title`, `intro`, `result_1_value`, `result_1_label`, `result_2_value`, `result_2_label`, `result_3_value`, `result_3_label`, `footnote`, `sort_order`, `is_active`) VALUES
('Case Study',
 'A supported living provider that had lost its last three tenders.',
 'Twelve months after their first consultation with ExcelBids:',
 '92/100', 'Evaluation score on a county council framework',
 '3-Year',  'Framework agreement secured',
 '0',       'Compliance rejections since onboarding',
 'Illustrative case study - replace with a verified client outcome before launch.',
 1, 1);

INSERT INTO `menu_items` (`location`, `label`, `url`, `sort_order`, `is_active`) VALUES
('primary', 'About Us',    '/#about',    1, 1),
('primary', 'Services',    '/#services', 2, 1),
('primary', 'Sectors',     '/#sectors',  3, 1),
('primary', 'Our Process', '/#process',  4, 1),
('primary', 'FAQs',        '/#faq',      5, 1),
('footer_company', 'About Us', '/#about',    1, 1),
('footer_company', 'Services', '/#services', 2, 1),
('footer_company', 'Why Us',   '/#why',      3, 1),
('footer_company', 'FAQ',      '/#faq',      4, 1),
('footer_start', 'Submit a Request', '/consultation', 1, 1),
('footer_start', 'Client Login',     '/portal/login', 2, 1);

INSERT INTO `pages` (`slug`, `title`, `body`, `meta_description`, `is_published`, `show_in_footer`, `sort_order`, `created_at`) VALUES
('privacy-policy', 'Privacy Policy',
 '<p>This policy explains what personal data ExcelBids collects, why we collect it, and how we look after it.</p><h3>What we collect</h3><p>When you submit a consultation request we collect your name, organisation, email address, phone number and anything you tell us about the opportunity. If you are a client with a portal account we also hold the documents and correspondence relating to your bids.</p><h3>Why we hold it</h3><p>To respond to your enquiry, to deliver the bid-writing work you have engaged us for, and to keep the records our professional obligations require.</p><h3>How long we keep it</h3><p>Enquiry records are held for 24 months. Client and bid records are held for six years from the end of the engagement.</p><h3>Your rights</h3><p>You can ask us for a copy of the data we hold about you, ask us to correct it, or ask us to delete it. Write to the contact address shown in the footer.</p><p><em>Replace this text with your own reviewed policy before launch.</em></p>',
 'How ExcelBids collects, uses and protects your personal data.', 1, 1, 1, NOW()),
('terms-of-service', 'Terms of Service',
 '<p>These terms apply to bid-writing, review and consultancy services supplied by ExcelBids.</p><h3>Engagement</h3><p>Work begins once a written scope and fee have been agreed. Fees are either fixed per project or charged at an agreed day rate.</p><h3>Confidentiality</h3><p>Every engagement is covered by a non-disclosure agreement. We do not name clients or reproduce their material without written permission.</p><h3>Outcomes</h3><p>We commit to a compliant, well-evidenced submission delivered to the deadline agreed. No consultancy can guarantee a contract award, and we do not.</p><h3>Client responsibilities</h3><p>Accurate source material, evidence and approvals must be supplied in time for us to meet the submission deadline.</p><p><em>Replace this text with your own reviewed terms before launch.</em></p>',
 'The terms under which ExcelBids supplies bid-writing and consultancy services.', 1, 1, 2, NOW());
