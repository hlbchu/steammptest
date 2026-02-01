-- Add text color field to nav_menu
ALTER TABLE `nav_menu` 
ADD COLUMN `text_color` varchar(20) DEFAULT NULL COMMENT 'Màu chữ menu (hex hoặc tên màu)' AFTER `icon`;

-- Update default colors for existing menus
UPDATE `nav_menu` SET `text_color` = '#ffffff' WHERE `text_color` IS NULL;
