-- Add purchase_type_id column to products table
ALTER TABLE products 
ADD COLUMN purchase_type_id INT UNSIGNED NULL AFTER category_id,
ADD INDEX idx_purchase_type_id (purchase_type_id);

-- Add foreign key (optional, comment out if you want flexibility)
-- ALTER TABLE products
-- ADD FOREIGN KEY (purchase_type_id) REFERENCES purchase_types(id) ON DELETE SET NULL;
