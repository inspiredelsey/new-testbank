-- Migration: add course pricing and the pending_checkouts table for the
-- combined signup+payment enrollment flow (Stripe, PayPal, Paystack,
-- Flutterwave — test/sandbox mode).
-- Safe to run once against an existing database that predates this feature.

ALTER TABLE courses
  ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER pass_percentage,
  ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'USD' AFTER price;

CREATE TABLE IF NOT EXISTS pending_checkouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  token VARCHAR(64) NOT NULL,
  course_id INT NOT NULL,
  existing_user_id INT NULL,
  name VARCHAR(150) NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NULL,
  gateway ENUM('stripe','paypal','paystack','flutterwave') NOT NULL,
  gateway_session_id VARCHAR(255) NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(3) NOT NULL,
  status ENUM('pending','completed','failed','expired') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP NULL,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (existing_user_id) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY idx_checkout_token (token),
  INDEX idx_checkout_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
