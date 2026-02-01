-- Add purchase types table
CREATE TABLE IF NOT EXISTS purchase_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add junction table for products and purchase types (many-to-many)
CREATE TABLE IF NOT EXISTS product_purchase_types (
    product_id INT UNSIGNED NOT NULL,
    purchase_type_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (product_id, purchase_type_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (purchase_type_id) REFERENCES purchase_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial purchase types
INSERT INTO purchase_types (name, slug, description) VALUES
('Game ROM', 'game-rom', 'Game ROM có thể chạy trên máy giả lập'),
('Game Steam Offline', 'game-steam-offline', 'Game Steam chơi offline không cần kết nối')
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description);
