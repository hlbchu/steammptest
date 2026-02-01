-- Add position column for banner placement
ALTER TABLE `banners`
  ADD COLUMN `position` ENUM('slide', 'category') NOT NULL DEFAULT 'slide' AFTER `link_url`;
