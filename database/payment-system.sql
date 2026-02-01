-- ============================================
-- PAYMENT SYSTEM MIGRATION
-- Chỉ tạo bảng payment + bank rotation
-- ============================================

USE steamweb;

-- ============================================
-- 1. PAYMENT_GATEWAYS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `payment_gateways` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'VD: SeaPay, PayPal, Stripe',
  `code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'VD: seapay, paypal, stripe',
  `api_key` VARCHAR(500) DEFAULT NULL,
  `api_secret` VARCHAR(500) DEFAULT NULL,
  `webhook_url` VARCHAR(500) DEFAULT NULL,
  `config_json` JSON DEFAULT NULL COMMENT 'Additional config as JSON',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_code` (`code`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. BANK_ACCOUNTS TABLE (User selectable banks)
-- ============================================
CREATE TABLE IF NOT EXISTS `bank_accounts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `gateway_id` INT UNSIGNED NOT NULL,
  `bank_name` VARCHAR(150) NOT NULL COMMENT 'VD: Vietcombank, Techcombank',
  `bank_code` VARCHAR(20) NOT NULL,
  `account_number` VARCHAR(50) DEFAULT NULL,
  `account_holder` VARCHAR(150) DEFAULT NULL,
  `icon_url` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `total_received` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng tiền đã nhận vào TK này',
  `max_amount_threshold` DECIMAL(15,2) DEFAULT NULL COMMENT 'Ngưỡng tối đa, sau đó chuyển sang bank khác (VD: 9900000)',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_gateway` (`gateway_id`),
  INDEX `idx_active` (`is_active`),
  INDEX `idx_total_received` (`total_received`),
  FOREIGN KEY (`gateway_id`) REFERENCES `payment_gateways`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. DEPOSIT_TRANSACTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `deposit_transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `bank_account_id` INT UNSIGNED DEFAULT NULL,
  `gateway_id` INT UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `fee` DECIMAL(15,2) DEFAULT 0.00,
  `final_amount` DECIMAL(15,2) NOT NULL COMMENT 'amount - fee',
  `status` ENUM('pending', 'success', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
  `transaction_code` VARCHAR(50) UNIQUE DEFAULT NULL,
  `gateway_reference` VARCHAR(100) DEFAULT NULL COMMENT 'External gateway transaction ID',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`gateway_id`) REFERENCES `payment_gateways`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEED DATA: PAYMENT GATEWAYS
-- ============================================
INSERT IGNORE INTO `payment_gateways` (`id`, `name`, `code`, `is_active`, `sort_order`) VALUES
(1, 'SeaPay', 'seapay', TRUE, 1),
(2, 'Ngân hàng trực tiếp', 'direct_bank', TRUE, 2);

-- ============================================
-- SEED DATA: BANK ACCOUNTS (3 ngân hàng với rotation threshold)
-- ============================================
INSERT IGNORE INTO `bank_accounts` (`id`, `gateway_id`, `bank_name`, `bank_code`, `account_number`, `account_holder`, `total_received`, `max_amount_threshold`, `is_active`, `sort_order`) VALUES
-- Ngân hàng 1: Vietcombank (dùng cho đơn < 9.9tr)
(1, 2, 'Vietcombank', 'VCB', '1020123456', 'NGUYEN VAN A', 0, 9900000.00, TRUE, 1),

-- Ngân hàng 2: Techcombank (dùng cho đơn 9.9tr - 20tr)
(2, 2, 'Techcombank', 'TCB', '5032123456', 'NGUYEN VAN B', 0, 20000000.00, TRUE, 2),

-- Ngân hàng 3: MB Bank (dùng cho đơn > 20tr hoặc khi 2 bank kia đầy)
(3, 2, 'MB Bank', 'MBBANK', '0123456789', 'NGUYEN VAN C', 0, NULL, TRUE, 3);

-- ============================================
-- TRIGGERS: Auto update bank total_received khi deposit success
-- ============================================
DROP TRIGGER IF EXISTS `trg_deposit_update_bank_total`;

DELIMITER $$
CREATE TRIGGER `trg_deposit_update_bank_total` AFTER UPDATE ON `deposit_transactions` 
FOR EACH ROW
BEGIN
    -- Khi deposit chuyển sang status = 'success', cộng tiền vào bank
    IF NEW.status = 'success' AND OLD.status != 'success' AND NEW.bank_account_id IS NOT NULL THEN
        UPDATE bank_accounts 
        SET total_received = total_received + NEW.amount 
        WHERE id = NEW.bank_account_id;
    END IF;
END$$
DELIMITER ;

-- ============================================
-- STORED PROCEDURE: Chọn ngân hàng phù hợp dựa trên total_received
-- ============================================
DROP PROCEDURE IF EXISTS `sp_get_available_bank`;

DELIMITER $$
CREATE PROCEDURE `sp_get_available_bank`()
BEGIN
    -- Tìm ngân hàng có total_received < max_amount_threshold
    -- Ưu tiên bank có sort_order thấp nhất (bank đầu tiên)
    SELECT * FROM bank_accounts
    WHERE is_active = TRUE 
    AND (
        max_amount_threshold IS NULL 
        OR total_received < max_amount_threshold
    )
    ORDER BY sort_order ASC
    LIMIT 1;
END$$
DELIMITER ;

-- ============================================
-- END OF PAYMENT SYSTEM MIGRATION
-- ============================================
