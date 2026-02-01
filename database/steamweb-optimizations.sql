-- SteamWeb Optimizations for game store
USE `steamweb`;

-- 1) Orders history (status changes)
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `old_status` ENUM('pending','paid','cancelled','refunded') NOT NULL,
  `new_status` ENUM('pending','paid','cancelled','refunded') NOT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `changed_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_order` (`order_id`),
  INDEX `idx_new_status` (`new_status`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Coupon usage tracking
CREATE TABLE IF NOT EXISTS `promotion_code_usages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `promotion_code_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED DEFAULT NULL,
  `used_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_code_user_order` (`promotion_code_id`, `user_id`, `order_id`),
  INDEX `idx_code` (`promotion_code_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_order` (`order_id`),
  FOREIGN KEY (`promotion_code_id`) REFERENCES `promotion_codes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Add missing columns for coupons/fees on orders
ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `user_id`,
  ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`,
  ADD COLUMN IF NOT EXISTS `final_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `discount_amount`,
  ADD COLUMN IF NOT EXISTS `promotion_code_id` INT UNSIGNED DEFAULT NULL AFTER `final_amount`,
  ADD COLUMN IF NOT EXISTS `note` VARCHAR(255) DEFAULT NULL AFTER `payment_method`;

ALTER TABLE `orders`
  ADD INDEX `idx_created_at` (`created_at`),
  ADD INDEX `idx_payment_method` (`payment_method`),
  ADD INDEX `idx_promo` (`promotion_code_id`);

ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_promo` FOREIGN KEY (`promotion_code_id`) REFERENCES `promotion_codes`(`id`) ON DELETE SET NULL;

-- 4) Make order_items subtotal optional and add index for fast reporting
ALTER TABLE `order_items`
  MODIFY `subtotal` DECIMAL(15,2) NOT NULL;

ALTER TABLE `order_items`
  ADD INDEX `idx_order_product` (`order_id`, `product_id`);

-- 5) Products: add received_content if missing (post-purchase content)
ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `received_content` TEXT NULL COMMENT 'Content received after purchase' AFTER `content`;

-- 6) Promotion codes: add min_order & per-user limit
ALTER TABLE `promotion_codes`
  ADD COLUMN IF NOT EXISTS `min_order_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `discount_amount`,
  ADD COLUMN IF NOT EXISTS `max_uses_per_user` INT NOT NULL DEFAULT 1 AFTER `max_uses`;

-- 7) Update existing orders totals from items (one-time recalculation)
UPDATE orders o
SET o.subtotal = (
    SELECT COALESCE(SUM(oi.subtotal), SUM(oi.price * oi.quantity), 0)
    FROM order_items oi
    WHERE oi.order_id = o.id
),
    o.discount_amount = 0.00,
    o.final_amount = (
    SELECT COALESCE(SUM(oi.subtotal), SUM(oi.price * oi.quantity), 0)
    FROM order_items oi
    WHERE oi.order_id = o.id
)
WHERE EXISTS (SELECT 1 FROM order_items oi2 WHERE oi2.order_id = o.id);

-- 8) Trigger to sync final_amount when total_amount changes
DROP TRIGGER IF EXISTS trg_orders_sync_amounts;
DELIMITER $$
CREATE TRIGGER trg_orders_sync_amounts
BEFORE UPDATE ON orders
FOR EACH ROW
BEGIN
  IF NEW.total_amount <> OLD.total_amount THEN
    SET NEW.subtotal = NEW.total_amount;
    SET NEW.final_amount = NEW.total_amount - NEW.discount_amount;
  END IF;
END$$
DELIMITER ;
