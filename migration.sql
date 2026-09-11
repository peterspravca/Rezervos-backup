-- VoľnéKreslo Dashboard Refactor Migration
-- Run this SQL against your MySQL database to add tables needed by the new dashboard pages.

-- ============================================================
-- 1. VOUCHERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `vouchers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(64) NOT NULL,
  `discount_type` ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `valid_until` DATE DEFAULT NULL,
  `max_uses` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = unlimited',
  `uses_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voucher_code_business` (`business_id`,`code`),
  KEY `idx_vouchers_business` (`business_id`),
  KEY `idx_vouchers_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. CLASSIFIEDS TABLE (inzeráty)
-- ============================================================
CREATE TABLE IF NOT EXISTS `classifieds` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('people_seek','people_offer','equipment','chair') NOT NULL,
  -- People (seek/offer)
  `listing_type` VARCHAR(32) DEFAULT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `specialization` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  -- Equipment
  `price` DECIMAL(10,2) DEFAULT NULL,
  `condition` VARCHAR(64) DEFAULT NULL,
  -- Chair rental
  `price_unit` VARCHAR(32) DEFAULT NULL COMMENT 'deň / mesiac',
  `available_from` DATE DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_classifieds_user` (`user_id`),
  KEY `idx_classifieds_type` (`type`),
  KEY `idx_classifieds_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. REVIEW REPLIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `review_replies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `review_id` INT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `reply_text` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reply_review` (`review_id`),
  KEY `idx_reply_business` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. REVIEWS TABLE (if not yet existing — for business/customer ratings)
-- ============================================================
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reviewer_id` INT UNSIGNED NOT NULL COMMENT 'who left the review',
  `reviewee_id` INT UNSIGNED NOT NULL COMMENT 'who was reviewed',
  `reviewer_type` ENUM('customer','business') NOT NULL DEFAULT 'customer',
  `reviewee_type` ENUM('business','customer') NOT NULL DEFAULT 'business',
  `business_id` INT UNSIGNED DEFAULT NULL COMMENT 'context business (appointment)',
  `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `comment` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_reviewee` (`reviewee_id`,`reviewee_type`),
  KEY `idx_reviews_reviewer` (`reviewer_id`,`reviewer_type`),
  KEY `idx_reviews_business` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SUBSCRIPTION COLUMNS pre establishments tabuľku
-- Tieto stĺpce chýbali — subscription_tier bol iba v users
-- ============================================================
ALTER TABLE `establishments`
  ADD COLUMN IF NOT EXISTS `subscription_tier` VARCHAR(20) DEFAULT 'free',
  ADD COLUMN IF NOT EXISTS `subscription_period` VARCHAR(20) DEFAULT 'monthly',
  ADD COLUMN IF NOT EXISTS `subscription_expires_at` DATETIME DEFAULT NULL;

-- Synchronizácia: skopíruj tier z users do establishments kde je nekonzistencia
UPDATE `establishments` e
JOIN `users` u ON e.user_id = u.id
SET 
  e.subscription_tier = u.subscription_tier,
  e.subscription_period = u.subscription_period,
  e.subscription_expires_at = u.subscription_expires_at
WHERE 
  u.subscription_tier IS NOT NULL 
  AND u.subscription_tier != ''
  AND (e.subscription_tier IS NULL OR e.subscription_tier = 'free')
  AND u.subscription_tier != 'free';
