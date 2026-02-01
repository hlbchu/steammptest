-- ============================================
-- BANK TRANSACTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bank_transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `bank_account_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `transaction_type` ENUM('in', 'out') NOT NULL DEFAULT 'in',
  `amount` DECIMAL(15,2) NOT NULL,
  `reference_code` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'completed',
  `sepay_id` VARCHAR(50) DEFAULT NULL UNIQUE,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_bank_account` (`bank_account_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_created` (`created_at`),
  INDEX `idx_sepay` (`sepay_id`),
  FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
