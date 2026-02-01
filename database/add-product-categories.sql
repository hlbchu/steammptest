-- ============================================
-- PRODUCT CATEGORIES - Nhiều sản phẩm - Nhiều danh mục
-- ============================================

USE steamweb;

-- Tạo bảng liên kết products - categories (nhiều-nhiều)
CREATE TABLE IF NOT EXISTS `product_categories` (
  `product_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`, `category_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  INDEX `idx_product` (`product_id`),
  INDEX `idx_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate data từ products.category_id sang product_categories
INSERT INTO product_categories (product_id, category_id)
SELECT id, category_id
FROM products
WHERE category_id IS NOT NULL;

-- Note: Không xóa cột category_id trong products để tương thích ngược
-- Có thể xóa sau khi đã chắc chắn migration thành công
-- ALTER TABLE products DROP FOREIGN KEY products_ibfk_1;
-- ALTER TABLE products DROP COLUMN category_id;
