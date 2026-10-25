-- ============================================================
-- CivicFix Pro — Smart City Grievance Management System
-- Database Schema v1.0
-- Engine: InnoDB | Charset: utf8mb4
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `civicfix` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `civicfix`;

-- ============================================================
-- ROLES
-- ============================================================
CREATE TABLE IF NOT EXISTS `roles` (
    `id`        TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_name` VARCHAR(30)      NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `role_name`) VALUES
    (1, 'citizen'),
    (2, 'work_official'),
    (3, 'department_admin'),
    (4, 'super_admin');

-- ============================================================
-- WARDS
-- ============================================================
CREATE TABLE IF NOT EXISTS `wards` (
    `id`        SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ward_name` VARCHAR(100)      NOT NULL,
    `ward_code` VARCHAR(20)       NOT NULL,
    `zone`      VARCHAR(60)       NULL DEFAULT NULL,
    `status`    ENUM('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ward_code` (`ward_code`),
    KEY `idx_ward_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DEPARTMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `departments` (
    `id`              SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `department_name` VARCHAR(100)      NOT NULL,
    `description`     TEXT              NULL DEFAULT NULL,
    `status`          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_dept_name` (`department_name`),
    KEY `idx_dept_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CITIZENS (users)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(120)     NOT NULL,
    `email`          VARCHAR(180)     NOT NULL,
    `phone`          VARCHAR(20)      NULL DEFAULT NULL,
    `password_hash`  VARCHAR(255)     NOT NULL,
    `ward_id`        SMALLINT UNSIGNED NULL DEFAULT NULL,
    `address`        TEXT             NULL DEFAULT NULL,
    `account_status` ENUM('active','suspended','pending') NOT NULL DEFAULT 'active',
    `created_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_email` (`email`),
    KEY `idx_user_ward` (`ward_id`),
    KEY `idx_user_status` (`account_status`),
    CONSTRAINT `fk_users_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- STAFF (work_official / department_admin / super_admin)
-- ============================================================
CREATE TABLE IF NOT EXISTS `staff` (
    `id`                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `name`                VARCHAR(120)     NOT NULL,
    `email`               VARCHAR(180)     NOT NULL,
    `phone`               VARCHAR(20)      NULL DEFAULT NULL,
    `password_hash`       VARCHAR(255)     NOT NULL,
    `role_id`             TINYINT UNSIGNED NOT NULL,
    `department_id`       SMALLINT UNSIGNED NULL DEFAULT NULL,
    `account_status`      ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    `availability_status` ENUM('available','unavailable','on_leave') NOT NULL DEFAULT 'available',
    `max_active_workload` TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `last_assigned_at`    DATETIME         NULL DEFAULT NULL,
    `created_at`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_staff_email` (`email`),
    KEY `idx_staff_role` (`role_id`),
    KEY `idx_staff_dept` (`department_id`),
    KEY `idx_staff_status` (`account_status`, `availability_status`),
    CONSTRAINT `fk_staff_role`       FOREIGN KEY (`role_id`)       REFERENCES `roles`       (`id`),
    CONSTRAINT `fk_staff_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- STAFF ↔ WARDS (many-to-many)
-- ============================================================
CREATE TABLE IF NOT EXISTS `staff_wards` (
    `id`       INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `staff_id` INT UNSIGNED     NOT NULL,
    `ward_id`  SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_staff_ward` (`staff_id`, `ward_id`),
    KEY `idx_sw_ward` (`ward_id`),
    CONSTRAINT `fk_sw_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sw_ward`  FOREIGN KEY (`ward_id`)  REFERENCES `wards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CATEGORIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id`                    SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_name`         VARCHAR(100)      NOT NULL,
    `description`           TEXT              NULL DEFAULT NULL,
    `department_id`         SMALLINT UNSIGNED NULL DEFAULT NULL,
    `default_priority`      ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    `verification_required` TINYINT(1)        NOT NULL DEFAULT 1,
    `status`                ENUM('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_category_name` (`category_name`),
    KEY `idx_cat_dept` (`department_id`),
    KEY `idx_cat_status` (`status`),
    CONSTRAINT `fk_category_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- COMPLAINTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `complaints` (
    `id`             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `complaint_code` VARCHAR(25)       NOT NULL,
    `citizen_id`     INT UNSIGNED      NOT NULL,
    `category_id`    SMALLINT UNSIGNED NOT NULL,
    `department_id`  SMALLINT UNSIGNED NULL DEFAULT NULL,
    `ward_id`        SMALLINT UNSIGNED NULL DEFAULT NULL,
    `title`          VARCHAR(255)      NOT NULL,
    `description`    TEXT              NOT NULL,
    `address`        VARCHAR(500)      NULL DEFAULT NULL,
    `latitude`       DECIMAL(10,7)     NULL DEFAULT NULL,
    `longitude`      DECIMAL(10,7)     NULL DEFAULT NULL,
    `priority`       ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    `status`         ENUM('pending','assigned','in_progress','awaiting_verification','rework_required','resolved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    `created_at`     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `resolved_at`    DATETIME          NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_complaint_code` (`complaint_code`),
    KEY `idx_complaint_citizen`   (`citizen_id`),
    KEY `idx_complaint_dept`      (`department_id`),
    KEY `idx_complaint_ward`      (`ward_id`),
    KEY `idx_complaint_status`    (`status`),
    KEY `idx_complaint_category`  (`category_id`),
    KEY `idx_complaint_priority`  (`priority`),
    KEY `idx_complaint_created`   (`created_at`),
    CONSTRAINT `fk_complaint_citizen`  FOREIGN KEY (`citizen_id`)   REFERENCES `users`       (`id`),
    CONSTRAINT `fk_complaint_category` FOREIGN KEY (`category_id`)  REFERENCES `categories`  (`id`),
    CONSTRAINT `fk_complaint_dept`     FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_complaint_ward`     FOREIGN KEY (`ward_id`)       REFERENCES `wards`       (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- COMPLAINT ASSIGNMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `complaint_assignments` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `complaint_id`      INT UNSIGNED NOT NULL,
    `official_id`       INT UNSIGNED NOT NULL,
    `assigned_by`       INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = automatic',
    `assignment_type`   ENUM('automatic','manual') NOT NULL DEFAULT 'automatic',
    `assignment_status` ENUM('active','cancelled','completed','superseded') NOT NULL DEFAULT 'active',
    `assignment_reason` TEXT         NULL DEFAULT NULL,
    `assigned_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `accepted_at`       DATETIME     NULL DEFAULT NULL,
    `completed_at`      DATETIME     NULL DEFAULT NULL,
    `ended_at`          DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ca_complaint`  (`complaint_id`),
    KEY `idx_ca_official`   (`official_id`),
    KEY `idx_ca_status`     (`assignment_status`),
    KEY `idx_ca_assigned_at` (`assigned_at`),
    CONSTRAINT `fk_ca_complaint`  FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`),
    CONSTRAINT `fk_ca_official`   FOREIGN KEY (`official_id`)  REFERENCES `staff`      (`id`),
    CONSTRAINT `fk_ca_assigner`   FOREIGN KEY (`assigned_by`)  REFERENCES `staff`      (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- COMPLAINT ATTACHMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `complaint_attachments` (
    `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `complaint_id`     INT UNSIGNED  NOT NULL,
    `uploaded_by`      INT UNSIGNED  NOT NULL COMMENT 'Can be citizen or staff id — see uploaded_by_role',
    `uploaded_by_role` ENUM('citizen','work_official','department_admin','super_admin') NOT NULL,
    `file_path`        VARCHAR(500)  NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `mime_type`        VARCHAR(100)  NOT NULL,
    `file_size`        INT UNSIGNED  NOT NULL,
    `attachment_type`  ENUM('complaint_photo','progress_photo','completion_evidence','other') NOT NULL DEFAULT 'complaint_photo',
    `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_attach_complaint` (`complaint_id`),
    KEY `idx_attach_uploader`  (`uploaded_by`),
    CONSTRAINT `fk_attach_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- COMPLAINT HISTORY (audit trail of status changes)
-- ============================================================
CREATE TABLE IF NOT EXISTS `complaint_history` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `complaint_id` INT UNSIGNED NOT NULL,
    `actor_id`    INT UNSIGNED NOT NULL,
    `actor_role`  ENUM('citizen','work_official','department_admin','super_admin','system') NOT NULL,
    `old_status`  VARCHAR(30)  NULL DEFAULT NULL,
    `new_status`  VARCHAR(30)  NOT NULL,
    `remarks`     TEXT         NULL DEFAULT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ch_complaint` (`complaint_id`),
    KEY `idx_ch_actor`     (`actor_id`),
    KEY `idx_ch_created`   (`created_at`),
    CONSTRAINT `fk_ch_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recipient_type` ENUM('citizen','work_official','department_admin','super_admin') NOT NULL,
    `recipient_id`   INT UNSIGNED NOT NULL,
    `complaint_id`   INT UNSIGNED NULL DEFAULT NULL,
    `title`          VARCHAR(255) NOT NULL,
    `message`        TEXT         NOT NULL,
    `is_read`        TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `read_at`        DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_notif_recipient` (`recipient_type`, `recipient_id`),
    KEY `idx_notif_complaint` (`complaint_id`),
    KEY `idx_notif_unread`    (`recipient_id`, `is_read`),
    CONSTRAINT `fk_notif_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- AUDIT LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_id`    INT UNSIGNED    NULL DEFAULT NULL,
    `actor_role`  ENUM('citizen','work_official','department_admin','super_admin','system') NOT NULL,
    `action`      VARCHAR(100)    NOT NULL,
    `entity_type` VARCHAR(50)     NULL DEFAULT NULL,
    `entity_id`   INT UNSIGNED    NULL DEFAULT NULL,
    `details`     JSON            NULL DEFAULT NULL,
    `ip_address`  VARCHAR(45)     NULL DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_actor`   (`actor_id`),
    KEY `idx_audit_entity`  (`entity_type`, `entity_id`),
    KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SYSTEM SETTINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id`           SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key`  VARCHAR(100)      NOT NULL,
    `setting_value` TEXT             NULL DEFAULT NULL,
    `updated_by`   INT UNSIGNED      NULL DEFAULT NULL,
    `updated_at`   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
    ('complaint_id_prefix',      'CFX'),
    ('max_upload_size_mb',       '5'),
    ('max_photos_per_complaint', '3'),
    ('default_max_workload',     '5'),
    ('app_name',                 'CivicFix Pro'),
    ('app_timezone',             'Asia/Kolkata'),
    ('sla_low_days',             '7'),
    ('sla_medium_days',          '5'),
    ('sla_high_days',            '3'),
    ('sla_critical_days',        '1');
