-- Test Bank Database Schema (MySQL 8, InnoDB)

CREATE DATABASE IF NOT EXISTS testbank;
USE testbank;

-- Disable foreign key checks to allow clean recreation
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS attempt_answers;
DROP TABLE IF EXISTS exam_attempts;
DROP TABLE IF EXISTS exam_rules;
DROP TABLE IF EXISTS exam_questions;
DROP TABLE IF EXISTS exams;
DROP TABLE IF EXISTS question_tags;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS question_options;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- Users table
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'instructor', 'student') NOT NULL,
  status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_role (role),
  INDEX idx_user_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table (supports parent/child hierarchy)
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_category_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Questions table
CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  type ENUM('mcq_single', 'mcq_multi', 'true_false', 'fill_blank', 'matching', 'essay') NOT NULL,
  question_text TEXT NOT NULL,
  difficulty ENUM('easy', 'medium', 'hard') NOT NULL,
  points DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_question_category (category_id),
  INDEX idx_question_type (type),
  INDEX idx_question_difficulty (difficulty),
  INDEX idx_question_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Question options table
CREATE TABLE question_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question_id INT NOT NULL,
  option_text TEXT NOT NULL,
  is_correct BOOLEAN NOT NULL DEFAULT FALSE,
  pair_key VARCHAR(255) NULL, -- used only for matching-type questions (left side link)
  order_index INT NOT NULL DEFAULT 0,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  INDEX idx_option_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tags table
CREATE TABLE tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Question tags mapping
CREATE TABLE question_tags (
  question_id INT NOT NULL,
  tag_id INT NOT NULL,
  PRIMARY KEY (question_id, tag_id),
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exams table
CREATE TABLE exams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  category_id INT NULL,
  duration_minutes INT NOT NULL DEFAULT 60,
  pass_percentage DECIMAL(5,2) NOT NULL DEFAULT 50.00,
  shuffle_questions BOOLEAN NOT NULL DEFAULT FALSE,
  shuffle_options BOOLEAN NOT NULL DEFAULT FALSE,
  max_attempts INT NOT NULL DEFAULT 0, -- 0 for unlimited
  start_date DATETIME NULL,
  end_date DATETIME NULL,
  status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_exam_category (category_id),
  INDEX idx_exam_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exam questions mapping (fixed questions)
CREATE TABLE exam_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_id INT NOT NULL,
  question_id INT NOT NULL,
  order_index INT NOT NULL DEFAULT 0,
  points_override DECIMAL(5,2) NULL,
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  INDEX idx_exam_question_exam (exam_id),
  INDEX idx_exam_question_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exam random pull rules
CREATE TABLE exam_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_id INT NOT NULL,
  category_id INT NOT NULL,
  difficulty ENUM('easy', 'medium', 'hard', 'any') NOT NULL DEFAULT 'any',
  question_count INT NOT NULL DEFAULT 1,
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  INDEX idx_exam_rule_exam (exam_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exam attempts table
CREATE TABLE exam_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_id INT NOT NULL,
  user_id INT NOT NULL,
  started_at DATETIME NOT NULL,
  submitted_at DATETIME NULL,
  status ENUM('in_progress', 'submitted', 'graded') NOT NULL DEFAULT 'in_progress',
  score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  passed BOOLEAN NOT NULL DEFAULT FALSE,
  -- We also store the resolved question list as a JSON array of question IDs in order to preserve order on page refresh
  resolved_questions JSON NULL,
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_attempt_exam (exam_id),
  INDEX idx_attempt_user (user_id),
  INDEX idx_attempt_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attempt answers
CREATE TABLE attempt_answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  attempt_id INT NOT NULL,
  question_id INT NOT NULL,
  answer_data JSON NULL, -- stores answer selections (single/multi-choice ids, matching pairs, filled text, essay text)
  is_correct BOOLEAN NULL,
  points_awarded DECIMAL(5,2) NULL,
  needs_manual_grading BOOLEAN NOT NULL DEFAULT FALSE,
  FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  INDEX idx_answer_attempt (attempt_id),
  INDEX idx_answer_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documents table for course content attachments
CREATE TABLE IF NOT EXISTS documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_type VARCHAR(50) NOT NULL,
  description TEXT NULL,
  status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
  order_index INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  INDEX idx_document_course (course_id),
  INDEX idx_document_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Links table for external resource URLs attached to a course
CREATE TABLE IF NOT EXISTS links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  url VARCHAR(500) NOT NULL,
  description TEXT NULL,
  order_index INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  INDEX idx_link_course (course_id),
  INDEX idx_link_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Learning Path Items table
CREATE TABLE IF NOT EXISTS learning_path_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  item_type ENUM('document', 'link', 'quiz') NOT NULL,
  item_id INT NOT NULL,
  order_index INT DEFAULT 0,
  prerequisite_item_id INT NULL,
  is_required BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (prerequisite_item_id) REFERENCES learning_path_items(id) ON DELETE SET NULL,
  INDEX idx_lpi_course (course_id),
  INDEX idx_lpi_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Learning Path Progress table
CREATE TABLE IF NOT EXISTS learning_path_progress (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  course_id INT NOT NULL,
  learning_path_item_id INT NOT NULL,
  status ENUM('locked', 'unlocked', 'in_progress', 'completed') DEFAULT 'locked',
  completed_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (learning_path_item_id) REFERENCES learning_path_items(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_user_lpi (user_id, learning_path_item_id),
  INDEX idx_lpp_user_course (user_id, course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NGN Cases table
CREATE TABLE IF NOT EXISTS cases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  scenario_text TEXT NOT NULL,
  category_id INT NOT NULL,
  is_trend BOOLEAN DEFAULT FALSE,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_case_category (category_id),
  INDEX idx_case_creator (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Case Exhibits table
CREATE TABLE IF NOT EXISTS case_exhibits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  case_id INT NOT NULL,
  tab_label VARCHAR(100) NOT NULL,
  content TEXT NOT NULL,
  timestamp_label VARCHAR(50) NULL,
  order_index INT DEFAULT 0,
  FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
  INDEX idx_exhibit_case (case_id),
  INDEX idx_exhibit_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NGN Questions Table (New definition or upgraded schema)
-- Note: We prefix the columns or tables where appropriate or drop the existing table if required, but the prompt says:
-- "DATABASE — add these tables to schema.sql (append, do not modify existing tables)"
-- So we add the table structure directly.
CREATE TABLE IF NOT EXISTS ngn_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  case_id INT NULL,
  case_order INT NULL,
  type ENUM('mcq_single','mcq_multi_sata','mcq_extended','true_false','matching','cloze_dropdown','cloze_dragdrop','drag_drop_ordered','matrix_single','matrix_multi','highlight','bowtie','fill_blank_calc','essay') NOT NULL,
  question_text TEXT NOT NULL,
  question_data JSON NOT NULL,
  difficulty ENUM('easy','medium','hard') NOT NULL,
  points DECIMAL(6,2) DEFAULT 1.00,
  scoring_method ENUM('all_or_nothing','partial_credit') DEFAULT 'all_or_nothing',
  status ENUM('draft','published') DEFAULT 'draft',
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_ngn_q_category (category_id),
  INDEX idx_ngn_q_case (case_id),
  INDEX idx_ngn_q_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Phase 4 Exam Engine Additions
ALTER TABLE exams ADD COLUMN course_id INT NULL;
ALTER TABLE exams ADD COLUMN gradebook_category ENUM('formative','summative') DEFAULT 'summative';

CREATE TABLE IF NOT EXISTS exam_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_id INT NOT NULL,
  question_id INT NOT NULL,
  order_index INT DEFAULT 0,
  points_override DECIMAL(6,2) NULL,
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_id INT NOT NULL,
  category_id INT NOT NULL,
  difficulty ENUM('easy','medium','hard','any') DEFAULT 'any',
  question_count INT,
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- STUDENT ATTEMPT FLOW TABLES (Appended in this step)
-- ============================================================

CREATE TABLE IF NOT EXISTS exam_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_id INT NOT NULL,
  user_id INT NOT NULL,
  started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  submitted_at TIMESTAMP NULL,
  status ENUM('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress',
  score DECIMAL(6,2) NULL,
  percentage DECIMAL(5,2) NULL,
  passed BOOLEAN NULL,
  resolved_question_ids JSON NULL,
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_attempt_exam (exam_id),
  INDEX idx_attempt_user (user_id),
  INDEX idx_attempt_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attempt_answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  attempt_id INT NOT NULL,
  question_id INT NOT NULL,
  answer_data JSON NULL,
  is_correct BOOLEAN NULL,
  points_awarded DECIMAL(6,2) NULL,
  needs_manual_grading BOOLEAN NOT NULL DEFAULT FALSE,
  FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  UNIQUE (attempt_id, question_id),
  INDEX idx_answer_attempt (attempt_id),
  INDEX idx_answer_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gradebook_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  item_type ENUM('quiz','manual') NOT NULL,
  item_id INT NULL,
  title VARCHAR(200) NOT NULL,
  weight DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  max_score DECIMAL(6,2) NOT NULL DEFAULT 100.00,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES exams(id) ON DELETE SET NULL,
  INDEX idx_grade_item_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gradebook_scores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gradebook_item_id INT NOT NULL,
  user_id INT NOT NULL,
  score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY idx_grade_score_unique (gradebook_item_id, user_id),
  FOREIGN KEY (gradebook_item_id) REFERENCES gradebook_items(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_grade_score_item (gradebook_item_id),
  INDEX idx_grade_score_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs for tracking student actions and engagement
CREATE TABLE IF NOT EXISTS activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  course_id INT NULL,
  action VARCHAR(100) NOT NULL,
  item_type VARCHAR(50) NULL,
  item_id INT NULL,
  meta JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
  INDEX idx_activity_user (user_id),
  INDEX idx_activity_course (course_id),
  INDEX idx_activity_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages table for user-to-user and group internal messaging
CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  recipient_id INT NULL,
  recipient_group_id INT NULL,
  course_id INT NULL,
  subject VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (recipient_group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
  INDEX idx_msg_sender (sender_id),
  INDEX idx_msg_recipient (recipient_id),
  INDEX idx_msg_group (recipient_group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tracks per-user read receipts for received messages
CREATE TABLE IF NOT EXISTS message_reads (
  message_id INT NOT NULL,
  user_id INT NOT NULL,
  read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (message_id, user_id),
  FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_msg_read_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;







