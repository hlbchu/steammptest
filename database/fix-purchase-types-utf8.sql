-- Fix UTF-8 encoding for purchase types
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

UPDATE purchase_types SET 
    name = 'Game ROM', 
    description = 'Game ROM có thể chạy trên máy giả lập' 
WHERE slug = 'game-rom';

UPDATE purchase_types SET 
    name = 'Game Steam Offline', 
    description = 'Game Steam chơi offline không cần kết nối' 
WHERE slug = 'game-steam-offline';
