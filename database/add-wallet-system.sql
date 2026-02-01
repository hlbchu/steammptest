-- ============================================
-- WALLET & TRANSACTION SYSTEM
-- ============================================

USE steamweb;

-- ============================================
-- USER_WALLETS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `user_wallets` (
  `user_id` INT UNSIGNED PRIMARY KEY,
  `balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền hiện có trong ví',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- WALLET_TRANSACTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('deposit', 'purchase', 'refund', 'withdrawal', 'admin') NOT NULL COMMENT 'Loại giao dịch',
  `amount` DECIMAL(15,2) NOT NULL COMMENT 'Số tiền giao dịch',
  `balance_before` DECIMAL(15,2) NOT NULL COMMENT 'Số dư trước giao dịch',
  `balance_after` DECIMAL(15,2) NOT NULL COMMENT 'Số dư sau giao dịch',
  `reference` VARCHAR(255) DEFAULT NULL COMMENT 'Mã tham chiếu (e.g. deposit_TXN123)',
  `transaction_id` VARCHAR(255) DEFAULT NULL COMMENT 'SeaPay transaction ID hoặc payment reference',
  `description` TEXT DEFAULT NULL COMMENT 'Mô tả giao dịch',
  `status` ENUM('pending', 'success', 'failed') NOT NULL DEFAULT 'success',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_type` (`type`),
  INDEX `idx_transaction_id` (`transaction_id`),
  INDEX `idx_reference` (`reference`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- END OF WALLET MIGRATION
-- ============================================
