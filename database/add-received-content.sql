-- Add received_content and content columns to products (for existing DBs)
USE `steamweb`;

ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `content` TEXT NULL COMMENT 'Detailed game content (HTML allowed)' AFTER `description`,
  ADD COLUMN IF NOT EXISTS `received_content` TEXT NULL COMMENT 'Content received after purchase' AFTER `content`;
