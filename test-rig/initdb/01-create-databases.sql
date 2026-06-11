-- Create both WordPress databases on first-boot
CREATE DATABASE IF NOT EXISTS wp_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS wp_upgrade CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON wp_dev.* TO 'wpuser'@'%';
GRANT ALL PRIVILEGES ON wp_upgrade.* TO 'wpuser'@'%';
FLUSH PRIVILEGES;
