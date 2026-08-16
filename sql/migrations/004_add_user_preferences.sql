-- Migration: add email_notifications and timezone preference columns to
-- users, for the Preferences tab in Account Settings.
-- Safe to run once against an existing database.

ALTER TABLE users
  ADD COLUMN email_notifications BOOLEAN NOT NULL DEFAULT TRUE AFTER status,
  ADD COLUMN timezone VARCHAR(50) NOT NULL DEFAULT 'UTC' AFTER email_notifications;
