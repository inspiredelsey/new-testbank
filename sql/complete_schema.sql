-- Test Bank LMS Complete Unified Schema (MySQL 8, InnoDB)
-- This file contains the complete consolidated database schema for Test Bank LMS (JoomLMS-Style).

CREATE DATABASE IF NOT EXISTS testbank;
USE testbank;

-- Disable foreign key checks to allow clean recreation of all tables
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS message_reads;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS group_members;
DROP TABLE IF EXISTS `groups`;
DROP TABLE IF EXISTS certificates;
DROP TABLE IF EXISTS certificate_templates;
DROP TABLE IF EXISTS gradebook_scores;
DROP TABLE IF EXISTS gradebook_items;
DROP TABLE IF EXISTS case_exhibits;
DROP TABLE IF EXISTS cases;
DROP TABLE IF EXISTS learning_path_progress;
DROP TABLE IF EXISTS learning_path_items;
DROP TABLE IF EXISTS links;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS attempt_answers;
DROP TABLE IF EXISTS exam_attempts;
DROP TABLE IF EXISTS exam_rules;
DROP TABLE IF EXISTS exam_questions;
DROP TABLE IF EXISTS exams;
DROP TABLE IF EXISTS question_tags;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS question_options;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS course_enrollments;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users table
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

-- 2. Categories table (supports parent/child hierarchy)
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

-- 3. Courses table
CREATE TABLE courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  category_id INT NULL,
  instructor_id INT NULL,
  thumbnail VARCHAR(255) NULL,
  status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  pass_percentage DECIMAL(5,2) NOT NULL DEFAULT 50.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_course_category (category_id),
  INDEX idx_course_instructor (instructor_id),
  INDEX idx_course_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Course enrollments table
CREATE TABLE course_enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  student_id INT NOT NULL,
  enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY idx_unique_enrollment (course_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. NGN Cases table
CREATE TABLE cases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  scenario_text TEXT NOT NULL,
  category_id INT NULL,
  is_trend BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_case_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Questions table (includes NGN types and Case study reference columns)
CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  type ENUM('mcq_single','mcq_multi','mcq_multi_sata','mcq_extended','true_false','matching','cloze_dropdown','cloze_dragdrop','drag_drop_ordered','matrix_single','matrix_multi','highlight','bowtie','fill_blank_calc','essay') NOT NULL,
  question_text TEXT NOT NULL,
  difficulty ENUM('easy', 'medium', 'hard') NOT NULL,
  points DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
  created_by INT NULL,
  case_id INT NULL,
  case_order INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE SET NULL,
  INDEX idx_question_category (category_id),
  INDEX idx_question_type (type),
  INDEX idx_question_difficulty (difficulty),
  INDEX idx_question_status (status),
  INDEX idx_question_case (case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Question options table
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

-- 8. Tags table
CREATE TABLE tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Question tags mapping
CREATE TABLE question_tags (
  question_id INT NOT NULL,
  tag_id INT NOT NULL,
  PRIMARY KEY (question_id, tag_id),
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Exams table (includes Course association)
CREATE TABLE exams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  category_id INT NULL,
  course_id INT NULL,
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
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  INDEX idx_exam_category (category_id),
  INDEX idx_exam_status (status),
  INDEX idx_exam_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Exam questions mapping (fixed questions)
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

-- 12. Exam random pull rules
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

-- 13. Exam attempts table
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
  resolved_questions JSON NULL, -- stores resolved question list as a JSON array of question IDs
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_attempt_exam (exam_id),
  INDEX idx_attempt_user (user_id),
  INDEX idx_attempt_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Attempt answers
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

-- 15. Documents table for course content attachments
CREATE TABLE documents (
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

-- 16. Links table
CREATE TABLE links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  url VARCHAR(500) NOT NULL,
  status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  INDEX idx_link_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Learning Path Items table
CREATE TABLE learning_path_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  type ENUM('document', 'link', 'quiz') NOT NULL,
  item_id INT NOT NULL, -- references id of documents, links, or exams
  prerequisite_id INT NULL, -- references another learning_path_items.id
  order_index INT NOT NULL DEFAULT 0,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (prerequisite_id) REFERENCES learning_path_items(id) ON DELETE SET NULL,
  INDEX idx_lp_course (course_id),
  INDEX idx_lp_item (type, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Learning Path Progress table
CREATE TABLE learning_path_progress (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  learning_path_item_id INT NOT NULL,
  completed BOOLEAN NOT NULL DEFAULT FALSE,
  completed_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (learning_path_item_id) REFERENCES learning_path_items(id) ON DELETE CASCADE,
  UNIQUE KEY idx_unique_lp_progress (user_id, learning_path_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Case Exhibits table
CREATE TABLE case_exhibits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  case_id INT NOT NULL,
  tab_label VARCHAR(100) NOT NULL,
  content TEXT NOT NULL,
  timestamp_label VARCHAR(100) NULL,
  order_index INT NOT NULL DEFAULT 0,
  FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
  INDEX idx_exhibit_case (case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Gradebook Items table
CREATE TABLE gradebook_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  type ENUM('quiz', 'assignment', 'attendance', 'other') NOT NULL,
  item_id INT NULL, -- if type is quiz, references exams.id
  max_points DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  weight DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  INDEX idx_gradebook_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Gradebook Scores table
CREATE TABLE gradebook_scores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gradebook_item_id INT NOT NULL,
  student_id INT NOT NULL,
  score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  graded_by INT NULL,
  graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (gradebook_item_id) REFERENCES gradebook_items(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY idx_unique_gradebook_score (gradebook_item_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Certificate Templates table
CREATE TABLE certificate_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL, -- HTML / Template string with tokens
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  INDEX idx_cert_template_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Issued Certificates table
CREATE TABLE certificates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  student_id INT NOT NULL,
  certificate_code VARCHAR(100) NOT NULL UNIQUE,
  issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_cert_course (course_id),
  INDEX idx_cert_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. Groups table
CREATE TABLE `groups` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. Group Members mapping table
CREATE TABLE group_members (
  group_id INT NOT NULL,
  user_id INT NOT NULL,
  PRIMARY KEY (group_id, user_id),
  FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. Messages table
CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  recipient_id INT NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_msg_sender (sender_id),
  INDEX idx_msg_recipient (recipient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 27. Message Reads tracking
CREATE TABLE message_reads (
  message_id INT NOT NULL,
  user_id INT NOT NULL,
  read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (message_id, user_id),
  FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 28. Activity Logs table
CREATE TABLE activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(255) NOT NULL,
  details TEXT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_activity_user (user_id)
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

