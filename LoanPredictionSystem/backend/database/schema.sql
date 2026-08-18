-- =============================================================================
-- LoanSecure - AI Loan Management & Default Prediction System
-- Database Schema
-- Engine: MySQL 5.7+ / MariaDB 10.3+
-- =============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `loansecure` 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `loansecure`;

-- =============================================================================
-- TABLE: users
-- Stores both customers and loan officers (role-based)
-- =============================================================================
CREATE TABLE `users` (
  `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `fullname`        VARCHAR(120)      NOT NULL,
  `email`           VARCHAR(180)      NOT NULL,
  `phone`           VARCHAR(20)       NOT NULL,
  `password_hash`   VARCHAR(255)      NOT NULL,
  `role`            ENUM('customer','officer') NOT NULL DEFAULT 'customer',
  `avatar`          VARCHAR(255)      NULL DEFAULT NULL,
  -- Customer-specific financial profile
  `income`          DECIMAL(15,2)     NULL DEFAULT NULL,
  `credit_score`    SMALLINT UNSIGNED NULL DEFAULT NULL,
  `address`         VARCHAR(255)      NULL DEFAULT NULL,
  -- Account state
  `is_active`       TINYINT(1)        NOT NULL DEFAULT 1,
  `email_verified`  TINYINT(1)        NOT NULL DEFAULT 0,
  `created_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: loan_applications
-- Core loan request records submitted by customers
-- =============================================================================
CREATE TABLE `loan_applications` (
  `id`                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `customer_id`         INT UNSIGNED      NOT NULL,
  `officer_id`          INT UNSIGNED      NULL DEFAULT NULL,
  `loan_number`         VARCHAR(20)       NOT NULL,               -- e.g. LN-00001
  `amount`              DECIMAL(15,2)     NOT NULL,
  `term_months`         TINYINT UNSIGNED  NOT NULL,               -- 12, 24, 36, 60
  `purpose`             VARCHAR(100)      NOT NULL,
  `employment_status`   VARCHAR(80)       NOT NULL,
  `annual_income`       DECIMAL(15,2)     NOT NULL,
  `status`              ENUM('pending','under_review','approved','rejected','disbursed','closed')
                                          NOT NULL DEFAULT 'pending',
  `officer_note`        TEXT              NULL DEFAULT NULL,
  `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_loan_number` (`loan_number`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_officer` (`officer_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_loan_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_loan_officer`
    FOREIGN KEY (`officer_id`)  REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: ml_predictions
-- Stores Python ML model output for each loan application
-- =============================================================================
CREATE TABLE `ml_predictions` (
  `id`                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `loan_id`               INT UNSIGNED  NOT NULL,
  `risk_score`            DECIMAL(5,4)  NOT NULL,                 -- 0.0000 – 1.0000
  `approval_probability`  DECIMAL(5,4)  NOT NULL,
  `default_probability`   DECIMAL(5,4)  NOT NULL,
  `risk_label`            ENUM('low','medium','high') NOT NULL,
  `model_version`         VARCHAR(30)   NOT NULL DEFAULT '1.0',
  `raw_response`          JSON          NULL DEFAULT NULL,        -- full Python JSON payload
  `predicted_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_loan_prediction` (`loan_id`),
  CONSTRAINT `fk_pred_loan`
    FOREIGN KEY (`loan_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: documents
-- Uploaded files associated with loan applications
-- =============================================================================
CREATE TABLE `documents` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `loan_id`       INT UNSIGNED  NULL DEFAULT NULL,
  `customer_id`   INT UNSIGNED  NOT NULL,
  `file_name`     VARCHAR(255)  NOT NULL,                         -- sanitized original name
  `stored_name`   VARCHAR(255)  NOT NULL,                         -- UUID-based storage name
  `file_type`     VARCHAR(80)   NOT NULL,                         -- MIME type
  `file_size`     INT UNSIGNED  NOT NULL,                         -- bytes
  `doc_type`      ENUM('identity','income','property','other') NOT NULL DEFAULT 'other',
  `verified`      TINYINT(1)    NOT NULL DEFAULT 0,
  `uploaded_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_doc_loan`     (`loan_id`),
  KEY `idx_doc_customer` (`customer_id`),
  CONSTRAINT `fk_doc_loan`
    FOREIGN KEY (`loan_id`)     REFERENCES `loan_applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_doc_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: notifications
-- System notifications for customers and officers
-- =============================================================================
CREATE TABLE `notifications` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED  NOT NULL,
  `title`       VARCHAR(150)  NOT NULL,
  `message`     TEXT          NOT NULL,
  `type`        ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `is_read`     TINYINT(1)    NOT NULL DEFAULT 0,
  `loan_id`     INT UNSIGNED  NULL DEFAULT NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user`  (`user_id`),
  KEY `idx_notif_read`  (`is_read`),
  CONSTRAINT `fk_notif_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: password_resets
-- Secure token-based password reset flow
-- =============================================================================
CREATE TABLE `password_resets` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED  NOT NULL,
  `token_hash`  VARCHAR(255)  NOT NULL,
  `expires_at`  DATETIME      NOT NULL,
  `used`        TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reset_user` (`user_id`),
  CONSTRAINT `fk_reset_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: audit_log
-- Immutable record of all critical actions (officer decisions, auth events)
-- =============================================================================
CREATE TABLE `audit_log` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED    NULL DEFAULT NULL,
  `action`      VARCHAR(100)    NOT NULL,
  `entity_type` VARCHAR(50)     NULL DEFAULT NULL,
  `entity_id`   INT UNSIGNED    NULL DEFAULT NULL,
  `detail`      TEXT            NULL DEFAULT NULL,
  `ip_address`  VARCHAR(45)     NULL DEFAULT NULL,
  `user_agent`  VARCHAR(255)    NULL DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user`   (`user_id`),
  KEY `idx_audit_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED: Default loan officer account
-- Password: Officer@1234  (change immediately in production)
-- =============================================================================
INSERT INTO `users` (`fullname`, `email`, `phone`, `password_hash`, `role`, `is_active`, `email_verified`)
VALUES (
  'Sarah Jenkins',
  'officer@loansecure.local',
  '0000000000',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password
  'officer',
  1,
  1
);
