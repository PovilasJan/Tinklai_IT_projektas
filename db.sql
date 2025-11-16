-- Schema for Viešbučių tinklas
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','employee','client') NOT NULL DEFAULT 'client',
  `reservation_count` INT NOT NULL DEFAULT 0,
  `total_spent` DECIMAL(10,2) NOT NULL DEFAULT 0.00
);

CREATE TABLE IF NOT EXISTS `hotels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `rating` DECIMAL(2,1) NOT NULL DEFAULT 0.0
);

CREATE TABLE IF NOT EXISTS `rooms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `hotel_id` INT NOT NULL,
  `places` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('available','maintenance') NOT NULL DEFAULT 'available',
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `reservations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `deposit_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_code_id` INT DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes`(`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `email` VARCHAR(150) NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `loyalty_tiers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `min_amount` DECIMAL(10,2) NOT NULL,
  `discount_percent` INT NOT NULL
);

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

-- Sample loyalty tiers (automatic discounts based on spending)
INSERT INTO `loyalty_tiers` (`min_amount`,`discount_percent`) VALUES
(1500.00,10),(1000.00,5),(500.00,3);

-- Update existing reservations to calculate total_price and deposit_amount if needed
-- This is useful when migrating from older schema versions
UPDATE reservations r
JOIN rooms rm ON r.room_id = rm.id
SET 
  r.total_price = rm.price * DATEDIFF(r.end_date, r.start_date),
  r.deposit_amount = rm.price * DATEDIFF(r.end_date, r.start_date) * 0.20
WHERE r.total_price = 0 OR r.deposit_amount = 0;
