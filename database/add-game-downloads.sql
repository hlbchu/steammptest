-- ============================================
-- ADD GAME DOWNLOADS SUPPORT TABLES
-- Description: Support for game downloads after purchase
-- Created: 31/01/2026
-- ============================================

USE `steamweb`;

-- ============================================
-- 1. GAME_DOWNLOADS TABLE
-- ============================================
-- Stores download links and information for each game
DROP TABLE IF EXISTS `game_downloads`;

CREATE TABLE `game_downloads` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED NOT NULL,
  `download_name` VARCHAR(255) NOT NULL COMMENT 'Game name for download',
  `download_url` VARCHAR(500) NOT NULL COMMENT 'Direct download link',
  `file_size` VARCHAR(50) DEFAULT NULL COMMENT 'Size: 50GB, 100GB, etc.',
  `download_method` VARCHAR(50) NOT NULL DEFAULT 'direct' COMMENT 'direct, torrent, mirror',
  `mirror_url` VARCHAR(500) DEFAULT NULL COMMENT 'Alternative download link',
  `version` VARCHAR(50) DEFAULT '1.0' COMMENT 'Game version',
  `system_requirements` TEXT DEFAULT NULL COMMENT 'Windows, Mac, Linux requirements',
  `description` TEXT DEFAULT NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_product_download` (`product_id`, `version`),
  INDEX `idx_product` (`product_id`),
  INDEX `idx_active` (`is_active`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. USER_GAME_DOWNLOADS TABLE
-- ============================================
-- Tracks which users can download which games (after purchase)
DROP TABLE IF EXISTS `user_game_downloads`;

CREATE TABLE `user_game_downloads` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED NOT NULL COMMENT 'Purchase order',
  `game_download_id` INT UNSIGNED NOT NULL,
  `download_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of times downloaded',
  `first_download_at` DATETIME DEFAULT NULL,
  `last_download_at` DATETIME DEFAULT NULL,
  `expires_at` DATETIME DEFAULT NULL COMMENT 'Download availability expiration date',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_product_download` (`user_id`, `product_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_product` (`product_id`),
  INDEX `idx_order` (`order_id`),
  INDEX `idx_active` (`is_active`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`game_download_id`) REFERENCES `game_downloads`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. DOWNLOAD_LOGS TABLE
-- ============================================
-- Track all download activities for analytics and support
DROP TABLE IF EXISTS `download_logs`;

CREATE TABLE `download_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `game_download_id` INT UNSIGNED NOT NULL,
  `download_url` VARCHAR(500) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL COMMENT 'Browser/Device info',
  `download_speed` VARCHAR(50) DEFAULT NULL COMMENT 'Download speed info',
  `status` ENUM('started', 'completed', 'failed', 'paused') NOT NULL DEFAULT 'started',
  `bytes_downloaded` BIGINT UNSIGNED DEFAULT 0,
  `error_message` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_product` (`product_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`game_download_id`) REFERENCES `game_downloads`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. DOWNLOAD_LINKS_HISTORY TABLE (Optional)
-- ============================================
-- Keep history of download link changes for troubleshooting
DROP TABLE IF EXISTS `download_links_history`;

CREATE TABLE `download_links_history` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `game_download_id` INT UNSIGNED NOT NULL,
  `old_url` VARCHAR(500) DEFAULT NULL,
  `new_url` VARCHAR(500) NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL COMMENT 'URL update reason',
  `changed_by` INT UNSIGNED DEFAULT NULL COMMENT 'Admin user id',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_game_download` (`game_download_id`),
  INDEX `idx_changed_by` (`changed_by`),
  FOREIGN KEY (`game_download_id`) REFERENCES `game_downloads`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAMPLE DATA: GAME DOWNLOADS
-- ============================================
INSERT INTO `game_downloads` (
  `product_id`,
  `download_name`,
  `download_url`,
  `file_size`,
  `download_method`,
  `mirror_url`,
  `version`,
  `system_requirements`,
  `description`,
  `is_active`
) VALUES
(1, 'ELDEN RING - Full Game', 'https://download.steamweb.local/elden-ring-v1.0.zip', '60GB', 'direct', 'https://mirror.steamweb.local/elden-ring-v1.0.zip', '1.0', 'Windows 10/11 64bit, Intel i5-10600K, RTX 2080, 60GB SSD', 'Full ELDEN RING game installer', TRUE),
(2, 'Black Myth: Wukong - Full Game', 'https://download.steamweb.local/wukong-v1.0.zip', '130GB', 'direct', 'https://mirror.steamweb.local/wukong-v1.0.zip', '1.0', 'Windows 10/11 64bit, Intel i9-10900K, RTX 3090, 130GB SSD', 'Full Black Myth: Wukong game installer', TRUE),
(3, 'Baldur\'s Gate 3 - Full Game', 'https://download.steamweb.local/baldurs-gate-3-v1.0.zip', '150GB', 'direct', 'https://mirror.steamweb.local/baldurs-gate-3-v1.0.zip', '1.0', 'Windows 10/11 64bit, Intel i7-10700K, RTX 2080, 150GB SSD', 'Full Baldur\'s Gate 3 game installer', TRUE),
(4, 'Cyberpunk 2077 - Full Game', 'https://download.steamweb.local/cyberpunk-v1.0.zip', '100GB', 'direct', 'https://mirror.steamweb.local/cyberpunk-v1.0.zip', '1.0', 'Windows 10/11 64bit, Intel i7-12700K, RTX 3080, 100GB SSD', 'Full Cyberpunk 2077 game installer with latest updates', TRUE),
(5, 'The Witcher 3 - Full Game', 'https://download.steamweb.local/witcher3-v1.0.zip', '136GB', 'direct', 'https://mirror.steamweb.local/witcher3-v1.0.zip', '1.0', 'Windows 10/11 64bit, Intel i7-12700K, RTX 3070, 136GB SSD', 'Full The Witcher 3: Wild Hunt game with DLC', TRUE);

-- ============================================
-- QUERIES TO VERIFY SETUP
-- ============================================
-- Get all available downloads for a game
-- SELECT * FROM game_downloads WHERE product_id = 1 AND is_active = TRUE;

-- Check download access for a user
-- SELECT ugd.*, p.name, gd.download_url 
-- FROM user_game_downloads ugd
-- JOIN products p ON ugd.product_id = p.id
-- JOIN game_downloads gd ON ugd.game_download_id = gd.id
-- WHERE ugd.user_id = 2 AND ugd.is_active = TRUE;

-- View download statistics
-- SELECT 
--   p.name as 'Game Name',
--   COUNT(DISTINCT udl.user_id) as 'Total Downloads',
--   COUNT(udl.id) as 'Download Attempts',
--   SUM(CASE WHEN udl.status = 'completed' THEN 1 ELSE 0 END) as 'Completed'
-- FROM products p
-- LEFT JOIN download_logs udl ON p.id = udl.product_id
-- GROUP BY p.id, p.name;
