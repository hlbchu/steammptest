-- ============================================
-- ADD CART AND PROMOTION TABLES
-- ============================================

-- Check if cart table exists before creating
DROP TABLE IF EXISTS `cart`;

-- ============================================
-- 1. CART TABLE (Shopping Cart)
-- ============================================
CREATE TABLE `cart` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `added_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_cart_item` (`user_id`, `product_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_product` (`product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `steamweb`.`users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `steamweb`.`products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. PROMOTION_CODES TABLE
-- ============================================
DROP TABLE IF EXISTS `promotion_codes`;

CREATE TABLE `promotion_codes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_percent` INT DEFAULT 0,
  `discount_amount` DECIMAL(15,2) DEFAULT 0,
  `max_uses` INT NOT NULL DEFAULT 999,
  `used_count` INT NOT NULL DEFAULT 0,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NOT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_code` (`code`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEED DATA: PROMOTION CODES
-- ============================================
INSERT INTO `promotion_codes` (`code`, `discount_percent`, `max_uses`, `start_date`, `end_date`, `status`) VALUES
('WELCOME2026', 10, 999, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'active'),
('STEAM50', 5, 500, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'active');
