-- Update Navigation Menu - Thêm link và mở rộng icon
-- Thêm cột link để điều hướng
ALTER TABLE `nav_menu` 
ADD COLUMN `link` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn/URL' AFTER `slug`;

-- Mở rộng icon để chứa SVG code đầy đủ
ALTER TABLE `nav_menu` 
MODIFY COLUMN `icon` text DEFAULT NULL COMMENT 'SVG code hoặc tên file SVG';

-- Cập nhật dữ liệu mặc định với link
UPDATE `nav_menu` SET `link` = '?page=home' WHERE `slug` = 'index';
UPDATE `nav_menu` SET `link` = '?page=hot' WHERE `slug` = 'hot';
UPDATE `nav_menu` SET `link` = '?page=new' WHERE `slug` = 'new';
UPDATE `nav_menu` SET `link` = '?page=steam-games' WHERE `slug` = 'steam-games';
UPDATE `nav_menu` SET `link` = '?page=search' WHERE `slug` = 'search';
