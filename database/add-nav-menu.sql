-- Navigation Menu Management System
-- Tạo bảng quản lý menu điều hướng

CREATE TABLE IF NOT EXISTS `nav_menu` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL COMMENT 'Tên menu hiển thị',
    `slug` varchar(100) NOT NULL COMMENT 'URL slug',
    `icon` varchar(50) DEFAULT NULL COMMENT 'Icon class hoặc emoji',
    `display_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự hiển thị',
    `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái hoạt động',
    `is_protected` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Menu được bảo vệ (không xóa được)',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `display_order` (`display_order`),
    KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm dữ liệu mặc định (giữ nguyên Trang chủ và Flash Sale)
INSERT INTO `nav_menu` (`name`, `slug`, `icon`, `display_order`, `is_active`, `is_protected`) VALUES
('Trang chủ', 'index', '🏠', 1, 1, 1),
('Flash Sale', 'hot', '🔥', 2, 1, 1),
('Game mới', 'new', '⭐', 3, 1, 0),
('Game Steam', 'steam-games', '🎮', 4, 1, 0),
('Tìm kiếm', 'search', '🔍', 5, 1, 0);
