-- Add parent_id for submenu support
ALTER TABLE `nav_menu` 
ADD COLUMN `parent_id` int(11) DEFAULT NULL COMMENT 'ID của menu cha (submenu)' AFTER `id`,
ADD KEY `parent_id` (`parent_id`),
ADD CONSTRAINT `nav_menu_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `nav_menu`(`id`) ON DELETE CASCADE;
