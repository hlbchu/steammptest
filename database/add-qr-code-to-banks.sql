-- ============================================
-- ADD QR CODE SUPPORT TO BANK ACCOUNTS
-- ============================================

USE steamweb;

-- Add QR code columns to bank_accounts table
ALTER TABLE `bank_accounts` 
ADD COLUMN `qr_code_data` LONGTEXT DEFAULT NULL COMMENT 'QR code data as base64 image' AFTER `icon_url`,
ADD COLUMN `qr_code_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL to QR code image' AFTER `qr_code_data`;

-- Add QR generation timestamp
ALTER TABLE `bank_accounts` 
ADD COLUMN `qr_generated_at` DATETIME DEFAULT NULL COMMENT 'Thời gian tạo mã QR' AFTER `qr_code_url`;

-- Create index for active banks with QR codes
CREATE INDEX `idx_active_with_qr` ON `bank_accounts`(`is_active`, `qr_code_data`);
