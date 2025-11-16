-- Update script to add discount_codes and loyalty_tiers tables
-- Run this if you already have the database set up

-- Create loyalty_tiers table
CREATE TABLE IF NOT EXISTS `loyalty_tiers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `min_amount` DECIMAL(10,2) NOT NULL,
  `discount_percent` INT NOT NULL
);

-- Create discount_codes table
CREATE TABLE IF NOT EXISTS `discount_codes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_percent` INT NOT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATE DEFAULT NULL,
  `usage_limit` INT DEFAULT NULL,
  `times_used` INT NOT NULL DEFAULT 0,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Add discount_code_id column to reservations table if it doesn't exist
-- First, check if column exists and add it
SET @dbname = DATABASE();
SET @tablename = 'reservations';
SET @columnname = 'discount_code_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  'ALTER TABLE `reservations` ADD COLUMN `discount_code_id` INT DEFAULT NULL'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add foreign key constraint if it doesn't exist
SET @constraintStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
      AND REFERENCED_TABLE_NAME = 'discount_codes'
  ) > 0,
  'SELECT 1',
  'ALTER TABLE `reservations` ADD CONSTRAINT `fk_discount_code` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes`(`id`) ON DELETE SET NULL'
));
PREPARE addConstraintIfNotExists FROM @constraintStatement;
EXECUTE addConstraintIfNotExists;
DEALLOCATE PREPARE addConstraintIfNotExists;

-- Insert sample loyalty tiers
INSERT IGNORE INTO `loyalty_tiers` (`min_amount`,`discount_percent`) VALUES
(1500.00,10),(1000.00,5),(500.00,3);
