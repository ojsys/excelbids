-- ExcelBids — Bid Management System
-- MySQL / MariaDB schema. Safe to import repeatedly on a fresh database.
-- Charset chosen for full emoji + accent support on shared cPanel MySQL 5.7+/MariaDB 10.2+.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- System configuration
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `settings` (
  `key`        VARCHAR(100) NOT NULL,
  `value`      TEXT NULL,
  `group_name` VARCHAR(50) NOT NULL DEFAULT 'general',
  `type`       VARCHAR(20) NOT NULL DEFAULT 'text',
  `label`      VARCHAR(160) NOT NULL DEFAULT '',
  `hint`       VARCHAR(255) NOT NULL DEFAULT '',
  `sort_order` INT NOT NULL DEFAULT 0,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`key`),
  KEY `idx_settings_group` (`group_name`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Staff accounts (admin panel)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(120) NOT NULL,
  `email`          VARCHAR(190) NOT NULL,
  `password_hash`  VARCHAR(255) NOT NULL,
  `role`           ENUM('admin','manager','writer','viewer') NOT NULL DEFAULT 'writer',
  `job_title`      VARCHAR(120) NOT NULL DEFAULT '',
  `phone`          VARCHAR(40) NOT NULL DEFAULT '',
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  `must_change_pw` TINYINT(1) NOT NULL DEFAULT 0,
  `reset_token`    VARCHAR(80) NULL,
  `reset_expires`  DATETIME NULL,
  `last_login_at`  DATETIME NULL,
  `last_login_ip`  VARCHAR(45) NULL,
  `created_at`     DATETIME NOT NULL,
  `updated_at`     DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`),
  KEY `idx_users_reset` (`reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Clients (CRM) and their portal logins
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `clients` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`     VARCHAR(30) NOT NULL,
  `organisation`  VARCHAR(190) NOT NULL,
  `contact_name`  VARCHAR(140) NOT NULL DEFAULT '',
  `email`         VARCHAR(190) NOT NULL DEFAULT '',
  `phone`         VARCHAR(40) NOT NULL DEFAULT '',
  `website`       VARCHAR(190) NOT NULL DEFAULT '',
  `company_no`    VARCHAR(40) NOT NULL DEFAULT '',
  `sector`        VARCHAR(120) NOT NULL DEFAULT '',
  `address_line1` VARCHAR(190) NOT NULL DEFAULT '',
  `address_line2` VARCHAR(190) NOT NULL DEFAULT '',
  `city`          VARCHAR(120) NOT NULL DEFAULT '',
  `postcode`      VARCHAR(20) NOT NULL DEFAULT '',
  `country`       VARCHAR(80) NOT NULL DEFAULT 'United Kingdom',
  `status`        ENUM('prospect','active','on_hold','archived') NOT NULL DEFAULT 'prospect',
  `owner_user_id` INT UNSIGNED NULL,
  `nda_signed_on` DATE NULL,
  `notes`         TEXT NULL,
  `created_at`    DATETIME NOT NULL,
  `updated_at`    DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_clients_reference` (`reference`),
  KEY `idx_clients_status` (`status`),
  KEY `idx_clients_owner` (`owner_user_id`),
  KEY `idx_clients_org` (`organisation`),
  CONSTRAINT `fk_clients_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `client_users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id`     INT UNSIGNED NOT NULL,
  `name`          VARCHAR(140) NOT NULL,
  `email`         VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NULL,
  `job_title`     VARCHAR(120) NOT NULL DEFAULT '',
  `phone`         VARCHAR(40) NOT NULL DEFAULT '',
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `is_primary`    TINYINT(1) NOT NULL DEFAULT 0,
  `invite_token`  VARCHAR(80) NULL,
  `invite_expires` DATETIME NULL,
  `reset_token`   VARCHAR(80) NULL,
  `reset_expires` DATETIME NULL,
  `last_login_at` DATETIME NULL,
  `last_login_ip` VARCHAR(45) NULL,
  `created_at`    DATETIME NOT NULL,
  `updated_at`    DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_client_users_email` (`email`),
  KEY `idx_client_users_client` (`client_id`),
  KEY `idx_client_users_invite` (`invite_token`),
  KEY `idx_client_users_reset` (`reset_token`),
  CONSTRAINT `fk_client_users_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Bids — the core work item
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `bids` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`            VARCHAR(40) NOT NULL,
  `client_id`            INT UNSIGNED NOT NULL,
  `title`                VARCHAR(255) NOT NULL,
  `buyer`                VARCHAR(190) NOT NULL DEFAULT '',
  `portal`               VARCHAR(120) NOT NULL DEFAULT '',
  `portal_ref`           VARCHAR(120) NOT NULL DEFAULT '',
  `service_type`         VARCHAR(120) NOT NULL DEFAULT '',
  `sector`               VARCHAR(120) NOT NULL DEFAULT '',
  `contract_value`       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `contract_length`      VARCHAR(60) NOT NULL DEFAULT '',
  `fee_type`             ENUM('fixed','day_rate','retainer','none') NOT NULL DEFAULT 'fixed',
  `fee_amount`           DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `stage`                ENUM('consultation','opportunity_review','strategy','drafting','qa','submission','post_submission') NOT NULL DEFAULT 'consultation',
  `status`               ENUM('draft','in_progress','submitted','won','lost','withdrawn','no_bid') NOT NULL DEFAULT 'draft',
  `win_probability`      TINYINT UNSIGNED NOT NULL DEFAULT 50,
  `clarification_due`    DATE NULL,
  `submission_due`       DATETIME NULL,
  `submitted_at`         DATETIME NULL,
  `decision_expected_on` DATE NULL,
  `outcome_on`           DATE NULL,
  `evaluation_score`     DECIMAL(5,2) NULL,
  `evaluation_max`       DECIMAL(5,2) NULL DEFAULT 100.00,
  `outcome_notes`        TEXT NULL,
  `summary`              TEXT NULL,
  `owner_user_id`        INT UNSIGNED NULL,
  `created_by`           INT UNSIGNED NULL,
  `created_at`           DATETIME NOT NULL,
  `updated_at`           DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bids_reference` (`reference`),
  KEY `idx_bids_client` (`client_id`),
  KEY `idx_bids_status` (`status`),
  KEY `idx_bids_stage` (`stage`),
  KEY `idx_bids_due` (`submission_due`),
  KEY `idx_bids_owner` (`owner_user_id`),
  CONSTRAINT `fk_bids_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bids_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bid_tasks` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bid_id`      INT UNSIGNED NOT NULL,
  `title`       VARCHAR(255) NOT NULL,
  `assignee_id` INT UNSIGNED NULL,
  `due_date`    DATE NULL,
  `is_done`     TINYINT(1) NOT NULL DEFAULT 0,
  `completed_at` DATETIME NULL,
  `sort_order`  INT NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bid_tasks_bid` (`bid_id`, `sort_order`),
  KEY `idx_bid_tasks_assignee` (`assignee_id`, `is_done`),
  CONSTRAINT `fk_bid_tasks_bid` FOREIGN KEY (`bid_id`) REFERENCES `bids` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bid_tasks_assignee` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The four-stage QA sign-off from the marketing site, tracked per bid.
CREATE TABLE IF NOT EXISTS `bid_qa_checks` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bid_id`     INT UNSIGNED NOT NULL,
  `check_key`  VARCHAR(60) NOT NULL,
  `title`      VARCHAR(190) NOT NULL,
  `is_passed`  TINYINT(1) NOT NULL DEFAULT 0,
  `score`      TINYINT UNSIGNED NULL,
  `notes`      TEXT NULL,
  `checked_by` INT UNSIGNED NULL,
  `checked_at` DATETIME NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bid_qa` (`bid_id`, `check_key`),
  CONSTRAINT `fk_bid_qa_bid` FOREIGN KEY (`bid_id`) REFERENCES `bids` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bid_qa_user` FOREIGN KEY (`checked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `documents` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id`      INT UNSIGNED NULL,
  `bid_id`         INT UNSIGNED NULL,
  `category`       VARCHAR(60) NOT NULL DEFAULT 'general',
  `original_name`  VARCHAR(255) NOT NULL,
  `stored_name`    VARCHAR(255) NOT NULL,
  `mime_type`      VARCHAR(120) NOT NULL DEFAULT '',
  `size_bytes`     INT UNSIGNED NOT NULL DEFAULT 0,
  `uploader_type`  ENUM('staff','client') NOT NULL DEFAULT 'staff',
  `uploader_id`    INT UNSIGNED NULL,
  `visible_to_client` TINYINT(1) NOT NULL DEFAULT 0,
  `notes`          VARCHAR(255) NOT NULL DEFAULT '',
  `created_at`     DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_documents_bid` (`bid_id`),
  KEY `idx_documents_client` (`client_id`),
  CONSTRAINT `fk_documents_bid` FOREIGN KEY (`bid_id`) REFERENCES `bids` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_documents_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Immutable activity feed rendered on the bid timeline and client portal.
CREATE TABLE IF NOT EXISTS `bid_events` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bid_id`      INT UNSIGNED NOT NULL,
  `event_type`  VARCHAR(40) NOT NULL DEFAULT 'note',
  `body`        TEXT NOT NULL,
  `actor_type`  ENUM('staff','client','system') NOT NULL DEFAULT 'staff',
  `actor_id`    INT UNSIGNED NULL,
  `actor_name`  VARCHAR(140) NOT NULL DEFAULT '',
  `visible_to_client` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bid_events_bid` (`bid_id`, `created_at`),
  CONSTRAINT `fk_bid_events_bid` FOREIGN KEY (`bid_id`) REFERENCES `bids` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Two-way messaging between the bid team and the client portal.
CREATE TABLE IF NOT EXISTS `messages` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id`   INT UNSIGNED NOT NULL,
  `bid_id`      INT UNSIGNED NULL,
  `sender_type` ENUM('staff','client') NOT NULL,
  `sender_id`   INT UNSIGNED NULL,
  `sender_name` VARCHAR(140) NOT NULL DEFAULT '',
  `body`        TEXT NOT NULL,
  `read_at`     DATETIME NULL,
  `created_at`  DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_messages_client` (`client_id`, `created_at`),
  KEY `idx_messages_bid` (`bid_id`),
  CONSTRAINT `fk_messages_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_bid` FOREIGN KEY (`bid_id`) REFERENCES `bids` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Consultation requests captured by the public site
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `enquiries` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`     VARCHAR(30) NOT NULL,
  `name`          VARCHAR(140) NOT NULL,
  `organisation`  VARCHAR(190) NOT NULL DEFAULT '',
  `email`         VARCHAR(190) NOT NULL,
  `phone`         VARCHAR(40) NOT NULL DEFAULT '',
  `service`       VARCHAR(140) NOT NULL DEFAULT '',
  `sector`        VARCHAR(140) NOT NULL DEFAULT '',
  `deadline`      DATE NULL,
  `message`       TEXT NULL,
  `status`        ENUM('new','contacted','qualified','converted','closed') NOT NULL DEFAULT 'new',
  `assigned_to`   INT UNSIGNED NULL,
  `client_id`     INT UNSIGNED NULL,
  `source`        VARCHAR(60) NOT NULL DEFAULT 'website',
  `ip_address`    VARCHAR(45) NOT NULL DEFAULT '',
  `admin_notes`   TEXT NULL,
  `created_at`    DATETIME NOT NULL,
  `updated_at`    DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_enquiries_reference` (`reference`),
  KEY `idx_enquiries_status` (`status`, `created_at`),
  CONSTRAINT `fk_enquiries_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_enquiries_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- CMS — content for the public site
-- ---------------------------------------------------------------------------

-- Singleton copy (headings, intros, button labels) grouped by page section.
CREATE TABLE IF NOT EXISTS `content_blocks` (
  `key`        VARCHAR(100) NOT NULL,
  `section`    VARCHAR(60) NOT NULL DEFAULT 'general',
  `label`      VARCHAR(160) NOT NULL DEFAULT '',
  `hint`       VARCHAR(255) NOT NULL DEFAULT '',
  `type`       ENUM('text','textarea','html','url','number') NOT NULL DEFAULT 'text',
  `value`      TEXT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`key`),
  KEY `idx_content_blocks_section` (`section`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Toggle + reorder whole sections of the home page.
CREATE TABLE IF NOT EXISTS `page_sections` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `section_key` VARCHAR(60) NOT NULL,
  `title`      VARCHAR(120) NOT NULL,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_page_sections_key` (`section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `services` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(160) NOT NULL,
  `description` TEXT NULL,
  `sort_order`  INT NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_services_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sectors` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120) NOT NULL,
  `is_core`    TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_sectors_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `process_steps` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(160) NOT NULL,
  `description` TEXT NULL,
  `sort_order`  INT NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_process_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `qa_checklist` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `check_key`   VARCHAR(60) NOT NULL,
  `title`       VARCHAR(160) NOT NULL,
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `sort_order`  INT NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_qa_key` (`check_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `why_cards` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `seal`        VARCHAR(8) NOT NULL DEFAULT '*',
  `title`       VARCHAR(160) NOT NULL,
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `sort_order`  INT NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_why_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stats` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `value`      VARCHAR(40) NOT NULL,
  `label`      VARCHAR(190) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_stats_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `portals` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(60) NOT NULL,
  `line_two`   VARCHAR(60) NOT NULL DEFAULT '',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_portals_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `faqs` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question`   VARCHAR(255) NOT NULL,
  `answer`     TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_faqs_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `testimonials` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quote`       TEXT NOT NULL,
  `author_role` VARCHAR(140) NOT NULL DEFAULT '',
  `author_org`  VARCHAR(140) NOT NULL DEFAULT '',
  `sort_order`  INT NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_testimonials_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `case_studies` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `eyebrow`    VARCHAR(60) NOT NULL DEFAULT 'Case Study',
  `title`      VARCHAR(255) NOT NULL,
  `intro`      VARCHAR(255) NOT NULL DEFAULT '',
  `result_1_value` VARCHAR(40) NOT NULL DEFAULT '',
  `result_1_label` VARCHAR(190) NOT NULL DEFAULT '',
  `result_2_value` VARCHAR(40) NOT NULL DEFAULT '',
  `result_2_label` VARCHAR(190) NOT NULL DEFAULT '',
  `result_3_value` VARCHAR(40) NOT NULL DEFAULT '',
  `result_3_label` VARCHAR(190) NOT NULL DEFAULT '',
  `footnote`   VARCHAR(255) NOT NULL DEFAULT '',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_case_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standalone pages (privacy, terms, and anything else added later).
CREATE TABLE IF NOT EXISTS `pages` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`        VARCHAR(120) NOT NULL,
  `title`       VARCHAR(190) NOT NULL,
  `layout_mode` ENUM('blocks','html') NOT NULL DEFAULT 'blocks',
  `body`        MEDIUMTEXT NULL,
  `meta_title`  VARCHAR(190) NOT NULL DEFAULT '',
  `meta_description` VARCHAR(255) NOT NULL DEFAULT '',
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `show_in_footer` TINYINT(1) NOT NULL DEFAULT 0,
  `show_page_header` TINYINT(1) NOT NULL DEFAULT 1,
  `hero_eyebrow` VARCHAR(120) NOT NULL DEFAULT '',
  `hero_intro`  VARCHAR(500) NOT NULL DEFAULT '',
  `sort_order`  INT NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL,
  `updated_at`  DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pages_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Page builder: ordered, optionally nested content blocks
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `page_blocks` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_id`      INT UNSIGNED NOT NULL,
  -- NULL for a top-level section; otherwise the section this block sits in.
  `parent_id`    INT UNSIGNED NULL,
  `block_type`   VARCHAR(40) NOT NULL,
  -- Which column of the parent section, 0-indexed.
  `column_index` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `sort_order`   INT NOT NULL DEFAULT 0,
  -- All per-block configuration, JSON encoded.
  `settings`     MEDIUMTEXT NULL,
  `is_visible`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   DATETIME NOT NULL,
  `updated_at`   DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_blocks_page` (`page_id`, `parent_id`, `column_index`, `sort_order`),
  CONSTRAINT `fk_blocks_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_blocks_parent` FOREIGN KEY (`parent_id`) REFERENCES `page_blocks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Media library — images used by page blocks
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `media` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Outcome letters — award and feedback letters published as public proof.
--
-- Deliberately separate from `documents`: those are private client files, these
-- are redacted copies the client has agreed we may show. Nothing here reaches
-- the public site until is_approved is set.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `outcome_letters` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`        VARCHAR(190) NOT NULL,
  `organisation` VARCHAR(140) NOT NULL DEFAULT '',
  `sector`       VARCHAR(140) NOT NULL DEFAULT '',
  `outcome`      VARCHAR(60) NOT NULL DEFAULT '',
  `received_on`  DATE NULL,
  `summary`      VARCHAR(500) NOT NULL DEFAULT '',
  `quote`        VARCHAR(600) NOT NULL DEFAULT '',
  `author_role`  VARCHAR(140) NOT NULL DEFAULT '',
  `author_org`   VARCHAR(140) NOT NULL DEFAULT '',
  `media_id`     INT UNSIGNED NULL,
  `is_approved`  TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order`   INT NOT NULL DEFAULT 0,
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_outcome_sort` (`is_active`, `is_approved`, `sort_order`),
  CONSTRAINT `fk_outcome_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menu_items` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `location`   ENUM('primary','footer_company','footer_start','footer_contact') NOT NULL DEFAULT 'primary',
  `label`      VARCHAR(120) NOT NULL,
  `url`        VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_menu_location` (`location`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Auditing and operational logs
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_type`  ENUM('staff','client','system') NOT NULL DEFAULT 'staff',
  `actor_id`    INT UNSIGNED NULL,
  `actor_name`  VARCHAR(140) NOT NULL DEFAULT '',
  `action`      VARCHAR(80) NOT NULL,
  `entity_type` VARCHAR(60) NOT NULL DEFAULT '',
  `entity_id`   INT UNSIGNED NULL,
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `ip_address`  VARCHAR(45) NOT NULL DEFAULT '',
  `created_at`  DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_created` (`created_at`),
  KEY `idx_activity_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_log` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `to_email`   VARCHAR(190) NOT NULL,
  `subject`    VARCHAR(255) NOT NULL,
  `body`       MEDIUMTEXT NULL,
  `status`     ENUM('sent','failed','queued') NOT NULL DEFAULT 'queued',
  `transport`  VARCHAR(20) NOT NULL DEFAULT 'mail',
  `error`      VARCHAR(500) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Brute-force throttling for both login surfaces.
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `guard`      VARCHAR(20) NOT NULL DEFAULT 'admin',
  `identifier` VARCHAR(190) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attempts_lookup` (`guard`, `identifier`, `created_at`),
  KEY `idx_attempts_ip` (`ip_address`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
