-- ============================================
-- STEAMWEB DATABASE MIGRATION
-- Created: 30/01/2026
-- Description: Complete database schema for Steam Game Store
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS `steamweb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `steamweb`;

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `avatar_url` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  `status` ENUM('active', 'banned') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_username` (`username`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. USER_PROFILES TABLE
-- ============================================
CREATE TABLE `user_profiles` (
  `user_id` INT UNSIGNED PRIMARY KEY,
  `vip_level` INT NOT NULL DEFAULT 0,
  `total_deposit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `monthly_deposit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `last_deposit_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. CATEGORIES TABLE (16 game categories)
-- ============================================
CREATE TABLE `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `icon` VARCHAR(50) DEFAULT 'icon-gamepad',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. PRODUCTS TABLE (Games with rating)
-- ============================================
CREATE TABLE `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `price` DECIMAL(15,2) NOT NULL,
  `sale_price` DECIMAL(15,2) DEFAULT NULL,
  `badge` VARCHAR(20) DEFAULT NULL COMMENT 'e.g., -20%, HOT, NEW',
  `image` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `content` TEXT DEFAULT NULL COMMENT 'Detailed game content (HTML allowed)',
  `received_content` TEXT DEFAULT NULL COMMENT 'Content received after purchase',
  `rating` DECIMAL(3,1) DEFAULT NULL COMMENT 'Rating 1-5 stars',
  `category_id` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('active', 'hidden') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_category` (`category_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. ORDERS TABLE
-- ============================================
CREATE TABLE `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `total_amount` DECIMAL(15,2) NOT NULL,
  `status` ENUM('pending', 'paid', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. ORDER_ITEMS TABLE
-- ============================================
CREATE TABLE `order_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `price` DECIMAL(15,2) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(15,2) NOT NULL,
  INDEX `idx_order` (`order_id`),
  INDEX `idx_product` (`product_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. WALLETS TABLE
-- ============================================
CREATE TABLE `wallets` (
  `user_id` INT UNSIGNED PRIMARY KEY,
  `balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. DEPOSITS TABLE (Top-up transactions)
-- ============================================
CREATE TABLE `deposits` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `method` VARCHAR(50) NOT NULL COMMENT 'Bank, Credit, E-wallet',
  `status` ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. TRANSACTIONS TABLE
-- ============================================
CREATE TABLE `transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('purchase', 'deposit', 'refund') NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_type` (`type`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. BANNERS TABLE
-- ============================================
CREATE TABLE `banners` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `link_url` VARCHAR(255) DEFAULT NULL,
  `position` ENUM('slide', 'category') NOT NULL DEFAULT 'slide',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `sort_order` INT NOT NULL DEFAULT 0,
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. POSTS TABLE (News/Guides)
-- ============================================
CREATE TABLE `posts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `excerpt` TEXT DEFAULT NULL,
  `content` TEXT DEFAULT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'blog' COMMENT 'blog, news, guide',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. SEARCH_LOGS TABLE (Optional)
-- ============================================
CREATE TABLE `search_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `keyword` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_keyword` (`keyword`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 13. SETTINGS TABLE (Optional)
-- ============================================
CREATE TABLE `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `value` TEXT DEFAULT NULL,
  INDEX `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEED DATA: CATEGORIES (16 game categories)
-- ============================================
INSERT INTO `categories` (`name`, `slug`, `icon`, `sort_order`, `is_active`) VALUES
('Chiến Thuật', 'strategy', 'icon-gamepad', 1, TRUE),
('Game HOT', 'hot-game', 'icon-fire', 2, TRUE),
('Game Indie', 'indie', 'icon-gamepad', 3, TRUE),
('Game Mô Phỏng', 'simulation', 'icon-gamepad', 4, TRUE),
('Game Online', 'online', 'icon-gamepad', 5, TRUE),
('Giải Lập - Simulator', 'emulator', 'icon-gamepad', 6, TRUE),
('Hành Động', 'action', 'icon-gamepad', 7, TRUE),
('Kịch Dịch', 'drama', 'icon-gamepad', 8, TRUE),
('List Game 29K', 'budget-games', 'icon-gamepad', 9, TRUE),
('Nỗ Thăng', 'adventure', 'icon-gamepad', 10, TRUE),
('Nhập Vũ - RPG', 'rpg', 'icon-gamepad', 11, TRUE),
('Phiêu Lưu', 'exploration', 'icon-gamepad', 12, TRUE),
('Tây Cảm', 'western', 'icon-gamepad', 13, TRUE),
('Thể Giới MỎ', 'sandbox', 'icon-gamepad', 14, TRUE),
('Thể Thao', 'sports', 'icon-gamepad', 15, TRUE),
('Việt Hóa', 'vietnamese-localized', 'icon-gamepad', 16, TRUE);

-- ============================================
-- SEED DATA: PRODUCTS (12 sample games with ratings)
-- ============================================
INSERT INTO `products` (`name`, `slug`, `price`, `sale_price`, `badge`, `image`, `description`, `rating`, `category_id`, `status`) VALUES
('ELDEN RING', 'elden-ring', 499000.00, 399200.00, '-20%', 'game1.jpg', 'Game nhập vai hành động thế giới mở từ FromSoftware', 4.8, 7, 'active'),
('Black Myth: Wukong', 'black-myth-wukong', 899000.00, 809100.00, '-10%', 'game3.jpg', 'Game hành động dựa trên Tây Du Ký', 4.7, 2, 'active'),
('Baldur\'s Gate 3', 'baldurs-gate-3', 599000.00, 509150.00, '-15%', 'game2.jpg', 'Game nhập vai chiến thuật theo lượt', 4.9, 11, 'active'),
('Cyberpunk 2077', 'cyberpunk-2077', 699000.00, 524250.00, '-25%', 'game4.jpg', 'Game nhập vai thế giới mở tương lai', 4.6, 11, 'active'),
('The Witcher 3: Wild Hunt', 'the-witcher-3', 299000.00, 209300.00, '-30%', 'game5.jpg', 'Game nhập vai phiêu lưu thế giới mở', 4.9, 11, 'active'),
('Starfield', 'starfield', 799000.00, 759050.00, '-5%', 'game6.jpg', 'Game nhập vai không gian từ Bethesda', 4.5, 11, 'active'),
('Final Fantasy VII Remake', 'final-fantasy-7-remake', 599000.00, 599000.00, 'HOT', 'game7.jpg', 'Game JRPG hành động remake từ bản gốc', 4.8, 11, 'active'),
('Dragon\'s Dogma 2', 'dragons-dogma-2', 699000.00, 615120.00, '-12%', 'game8.jpg', 'Game nhập vai hành động thế giới mở', 4.4, 7, 'active'),
('Palworld', 'palworld', 399000.00, 319200.00, '-20%', 'game9.jpg', 'Game sinh tồn với yếu tố thu thập sinh vật', 4.3, 14, 'active'),
('Hollow Knight', 'hollow-knight', 99000.00, 99000.00, 'NEW', 'game10.jpg', 'Game nhập vai hành động 2D phong cách Metroidvania', 4.7, 3, 'active'),
('Tekken 8', 'tekken-8', 699000.00, 643080.00, '-8%', 'game11.jpg', 'Game đối kháng đối đầu 1v1', 4.6, 15, 'active'),
('Street Fighter 6', 'street-fighter-6', 599000.00, 509150.00, '-15%', 'game12.jpg', 'Game đối kháng kinh điển từ Capcom', 4.7, 15, 'active');

-- ============================================
-- SEED DATA: ADMIN USER
-- ============================================
-- Password: admin123 (hashed with password_hash in PHP)
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`, `status`) VALUES
('admin', 'admin@steamweb.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 'active');

-- Create admin profile
INSERT INTO `user_profiles` (`user_id`, `vip_level`, `total_deposit`, `monthly_deposit`) VALUES
(1, 5, 0.00, 0.00);

-- Create admin wallet
INSERT INTO `wallets` (`user_id`, `balance`) VALUES
(1, 0.00);

-- ============================================
-- SEED DATA: SAMPLE BANNERS
-- ============================================
INSERT INTO `banners` (`title`, `image_url`, `link_url`, `is_active`, `sort_order`) VALUES
('Black Myth Wukong - Sale 10%', 'banner1.jpg', '/views/client/steam-games.php', TRUE, 1),
('Elden Ring - Giảm 20%', 'banner2.jpg', '/views/client/steam-games.php', TRUE, 2),
('Baldur\'s Gate 3 - HOT', 'banner3.jpg', '/views/client/steam-games.php', TRUE, 3);

-- ============================================
-- SEED DATA: SAMPLE POSTS
-- ============================================
INSERT INTO `posts` (`title`, `slug`, `excerpt`, `content`, `image_url`, `category`, `is_active`) VALUES
('Top 10 Game Hay Nhất 2026', 'top-10-game-hay-nhat-2026', 'Tổng hợp những tựa game được đánh giá cao nhất năm 2026', 'Nội dung chi tiết về top 10 game hay nhất...', 'post1.jpg', 'blog', TRUE),
('Hướng Dẫn Mua Game Trên Steam', 'huong-dan-mua-game-steam', 'Hướng dẫn chi tiết cách mua và kích hoạt game Steam', 'Nội dung hướng dẫn chi tiết...', 'post2.jpg', 'guide', TRUE),
('Black Myth Wukong Chính Thức Ra Mắt', 'black-myth-wukong-ra-mat', 'Game Tây Du Ký đình đám chính thức phát hành', 'Tin tức chi tiết về sự kiện ra mắt...', 'post3.jpg', 'news', TRUE);

-- ============================================
-- 11. CART TABLE (Shopping Cart)
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
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. PROMOTION_CODES TABLE
-- ============================================
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
-- 14. WALLET TRANSACTIONS TABLE
-- ============================================
CREATE TABLE `wallet_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` ENUM('deposit', 'purchase', 'refund', 'adjustment') DEFAULT 'deposit',
  `amount` DECIMAL(12, 2) NOT NULL,
  `reference_id` VARCHAR(100),
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_type` (`type`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 15. PAYMENT_GATEWAYS TABLE
-- ============================================
CREATE TABLE `payment_gateways` (
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
-- 16. BANK_ACCOUNTS TABLE (User selectable banks)
-- ============================================
CREATE TABLE `bank_accounts` (
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
-- 17. DEPOSIT_TRANSACTIONS TABLE
-- ============================================
CREATE TABLE `deposit_transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `bank_account_id` INT UNSIGNED DEFAULT NULL,
  `gateway_id` INT UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `fee` DECIMAL(15,2) DEFAULT 0.00,
  `final_amount` DECIMAL(15,2) NOT NULL COMMENT 'amount - fee',
  `status` ENUM('pending', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
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
INSERT INTO `payment_gateways` (`name`, `code`, `is_active`, `sort_order`) VALUES
('SeaPay', 'seapay', TRUE, 1),
('Ngân hàng trực tiếp', 'direct_bank', TRUE, 2);

-- ============================================
-- SEED DATA: BANK ACCOUNTS (3 ngân hàng với rotation threshold)
-- ============================================
INSERT INTO `bank_accounts` (`gateway_id`, `bank_name`, `bank_code`, `account_number`, `account_holder`, `total_received`, `max_amount_threshold`, `is_active`, `sort_order`) VALUES
-- Ngân hàng 1: Vietcombank (dùng cho đơn < 9.9tr)
(2, 'Vietcombank', 'VCB', '1020123456', 'NGUYEN VAN A', 0, 9900000.00, TRUE, 1),

-- Ngân hàng 2: Techcombank (dùng cho đơn 9.9tr - 20tr)
(2, 'Techcombank', 'TCB', '5032123456', 'NGUYEN VAN B', 0, 20000000.00, TRUE, 2),

-- Ngân hàng 3: MB Bank (dùng cho đơn > 20tr hoặc khi 2 bank kia đầy)
(2, 'MB Bank', 'MBBANK', '0123456789', 'NGUYEN VAN C', 0, NULL, TRUE, 3);

-- ============================================
-- TRIGGERS: Auto update bank total_received khi deposit success
-- ============================================
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
-- SEED DATA: PROMOTION CODES
-- ============================================
INSERT INTO `promotion_codes` (`code`, `discount_percent`, `max_uses`, `start_date`, `end_date`, `status`) VALUES
('WELCOME2026', 10, 999, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'active'),
('STEAM50', 5, 500, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'active');

-- ============================================
-- 13. SEED DATA: SETTINGS
-- ============================================
INSERT INTO `settings` (`key`, `value`) VALUES
('site_name', 'GAMES STORE'),
('site_email', 'contact@steamweb.com'),
('maintenance_mode', '0');

-- ============================================
-- END OF MIGRATION
-- ============================================
