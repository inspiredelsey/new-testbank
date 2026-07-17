-- MySQL 8.0 Seed Script for LMS Bank
-- This file seeds a default administrator user account.

-- PLAINTEXT PASSWORD REFERENCE FOR FIRST LOGIN: ChangeMe123!
-- Please change this password immediately after logging in for the first time.

INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `status`) 
VALUES (
  'Site Admin', 
  'admin@example.com', 
  '$2y$10$.iBAYOEPecihlF8Mepq3he9v7RzPISZea9eALOUxrB6cVZ3P8foh.', -- bcrypt hash of 'ChangeMe123!'
  'admin', 
  'active'
);
