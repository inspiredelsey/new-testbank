-- ============================================================
-- TEST SEED DATA — reusable test data for the Test Bank LMS
-- ============================================================
-- Run this against a database that already has the schema applied
-- (via sql/schema.sql). Safe to re-run on a fresh/reset database —
-- if run against a database that already has data, duplicate emails/
-- slugs will simply fail on their UNIQUE constraints (harmless).
--
-- ALL TEST ACCOUNTS USE THE SAME PASSWORD: Test1234!
-- (real bcrypt hash below, verified working with PHP's password_verify())
-- ============================================================

-- ---------------- USERS ----------------
-- 2 instructors, 3 students. Password for all: Test1234!
INSERT INTO users (name, email, password_hash, role, status) VALUES
('Dr. Amara Okafor', 'instructor1@test.com', '$2b$10$3MfprTLebEQbYuoDBrBwEu/3kbFjG78mogfgG4yUKuE0YP.VKII9a', 'instructor', 'active');
SET @instr1 = LAST_INSERT_ID();

INSERT INTO users (name, email, password_hash, role, status) VALUES
('Prof. James Whitfield', 'instructor2@test.com', '$2b$10$3MfprTLebEQbYuoDBrBwEu/3kbFjG78mogfgG4yUKuE0YP.VKII9a', 'instructor', 'active');
SET @instr2 = LAST_INSERT_ID();

INSERT INTO users (name, email, password_hash, role, status) VALUES
('Chidinma Eze', 'student1@test.com', '$2b$10$3MfprTLebEQbYuoDBrBwEu/3kbFjG78mogfgG4yUKuE0YP.VKII9a', 'student', 'active');
SET @stud1 = LAST_INSERT_ID();

INSERT INTO users (name, email, password_hash, role, status) VALUES
('Michael Adeyemi', 'student2@test.com', '$2b$10$3MfprTLebEQbYuoDBrBwEu/3kbFjG78mogfgG4yUKuE0YP.VKII9a', 'student', 'active');
SET @stud2 = LAST_INSERT_ID();

INSERT INTO users (name, email, password_hash, role, status) VALUES
('Sarah Johnson', 'student3@test.com', '$2b$10$3MfprTLebEQbYuoDBrBwEu/3kbFjG78mogfgG4yUKuE0YP.VKII9a', 'student', 'active');
SET @stud3 = LAST_INSERT_ID();

-- ---------------- CATEGORIES ----------------
-- One parent, three children (tests the nested category tree)
INSERT INTO categories (parent_id, name, slug, description) VALUES
(NULL, 'Nursing Fundamentals', 'nursing-fundamentals', 'Core nursing knowledge and licensure exam preparation');
SET @cat_parent = LAST_INSERT_ID();

INSERT INTO categories (parent_id, name, slug, description) VALUES
(@cat_parent, 'Medical-Surgical Nursing', 'medical-surgical-nursing', 'Adult health and medical-surgical nursing concepts');
SET @cat_medsurg = LAST_INSERT_ID();

INSERT INTO categories (parent_id, name, slug, description) VALUES
(@cat_parent, 'Pharmacology', 'pharmacology', 'Medication administration, dosage calculation, and drug classifications');
SET @cat_pharm = LAST_INSERT_ID();

INSERT INTO categories (parent_id, name, slug, description) VALUES
(@cat_parent, 'Pediatric Nursing', 'pediatric-nursing', 'Care of infants, children, and adolescents');
SET @cat_peds = LAST_INSERT_ID();

-- ---------------- COURSES ----------------
-- 4 published courses, varied prices/currencies, tied to different instructors/categories
INSERT INTO courses (title, description, category_id, instructor_id, status, pass_percentage, price, currency) VALUES
('NCLEX Med-Surg Mastery', 'A comprehensive review of medical-surgical nursing concepts with NGN-style practice questions covering cardiovascular, respiratory, and renal systems.', @cat_medsurg, @instr1, 'published', 75.00, 49.99, 'USD');
SET @course1 = LAST_INSERT_ID();

INSERT INTO courses (title, description, category_id, instructor_id, status, pass_percentage, price, currency) VALUES
('Pharmacology Essentials for Nurses', 'Master dosage calculations, drug classifications, and safe medication administration principles for licensure exam success.', @cat_pharm, @instr1, 'published', 70.00, 39.99, 'USD');
SET @course2 = LAST_INSERT_ID();

INSERT INTO courses (title, description, category_id, instructor_id, status, pass_percentage, price, currency) VALUES
('Pediatric Nursing Board Review', 'Focused review of pediatric growth and development, common childhood illnesses, and family-centered care principles.', @cat_peds, @instr2, 'published', 75.00, 15000.00, 'NGN');
SET @course3 = LAST_INSERT_ID();

INSERT INTO courses (title, description, category_id, instructor_id, status, pass_percentage, price, currency) VALUES
('Complete NCLEX-RN Prep Bundle', 'An all-in-one review bundle spanning every major nursing content area, with unfolding case studies and full-length practice exams.', @cat_parent, @instr2, 'published', 80.00, 89.99, 'USD');
SET @course4 = LAST_INSERT_ID();

-- ---------------- ENROLLMENTS ----------------
-- student1: enrolled in course1 only (tests "enrolled in this one, not others")
-- student2: enrolled in course1 AND course2 (tests logged-in-not-enrolled checkout path for course3/4)
-- student3: enrolled in nothing (tests fully anonymous-equivalent / fresh checkout flow while logged in)
INSERT INTO course_enrollments (course_id, student_id, status) VALUES
(@course1, @stud1, 'active'),
(@course1, @stud2, 'active'),
(@course2, @stud2, 'active');

-- ---------------- CASE STUDY (for NGN-style unfolding case questions) ----------------
INSERT INTO cases (title, scenario_text, category_id, is_trend, created_by) VALUES
('68-Year-Old with Shortness of Breath', 'A 68-year-old patient is admitted to the medical-surgical unit reporting progressive shortness of breath over the past 3 days, accompanied by bilateral ankle swelling and a 4 lb weight gain. The patient has a history of hypertension and reports occasional chest tightness with exertion.', @cat_medsurg, 0, @instr1);
SET @case1 = LAST_INSERT_ID();

INSERT INTO case_exhibits (case_id, tab_label, content, order_index) VALUES
(@case1, 'Vitals', 'BP: 158/94 mmHg | HR: 102 bpm | RR: 24/min | SpO2: 91% on room air | Temp: 98.2°F', 1),
(@case1, 'Nurses Notes', 'Patient sitting upright in bed, using accessory muscles to breathe. Bilateral crackles auscultated in lower lung fields. 2+ pitting edema noted bilaterally in lower extremities.', 2),
(@case1, 'Labs', 'BNP: 850 pg/mL (elevated) | Sodium: 133 mEq/L | Potassium: 4.1 mEq/L | Creatinine: 1.2 mg/dL', 3);

-- ---------------- QUESTIONS ----------------
-- A representative mix of NGN question types, attached to course1 (Med-Surg)

-- 1. mcq_single
INSERT INTO questions (category_id, type, question_text, question_data, difficulty, points, scoring_method, status, created_by) VALUES
(@cat_medsurg, 'mcq_single', 'A patient with heart failure has a BNP of 850 pg/mL. What does this finding most likely indicate?',
'{"options":[{"id":"o1","text":"Normal cardiac function","is_correct":false},{"id":"o2","text":"Fluid volume overload consistent with heart failure","is_correct":true},{"id":"o3","text":"Acute kidney injury","is_correct":false},{"id":"o4","text":"Respiratory infection","is_correct":false}]}',
'medium', 1.00, 'all_or_nothing', 'published', @instr1);
SET @q1 = LAST_INSERT_ID();

-- 2. mcq_multi_sata
INSERT INTO questions (category_id, type, question_text, question_data, difficulty, points, scoring_method, status, created_by) VALUES
(@cat_medsurg, 'mcq_multi_sata', 'Select all findings consistent with left-sided heart failure. (Select all that apply)',
'{"options":[{"id":"o1","text":"Bilateral crackles on auscultation","is_correct":true},{"id":"o2","text":"Peripheral (ankle) edema","is_correct":false},{"id":"o3","text":"Shortness of breath on exertion","is_correct":true},{"id":"o4","text":"Jugular vein distention","is_correct":false},{"id":"o5","text":"Orthopnea","is_correct":true}]}',
'hard', 2.00, 'partial_credit', 'published', @instr1);
SET @q2 = LAST_INSERT_ID();

-- 3. true_false
INSERT INTO questions (category_id, type, question_text, question_data, difficulty, points, scoring_method, status, created_by) VALUES
(@cat_medsurg, 'true_false', 'A BNP level below 100 pg/mL rules out heart failure as the cause of dyspnea.',
'{"options":[{"id":"o1","text":"True","is_correct":true},{"id":"o2","text":"False","is_correct":false}]}',
'easy', 1.00, 'all_or_nothing', 'published', @instr1);
SET @q3 = LAST_INSERT_ID();

-- 4. matching
INSERT INTO questions (category_id, type, question_text, question_data, difficulty, points, scoring_method, status, created_by) VALUES
(@cat_pharm, 'matching', 'Match each medication class to its primary mechanism of action.',
'{"left":[{"id":"l1","text":"ACE Inhibitors"},{"id":"l2","text":"Beta-Blockers"},{"id":"l3","text":"Loop Diuretics"}],"right":[{"id":"r1","text":"Blocks conversion of angiotensin I to angiotensin II"},{"id":"r2","text":"Reduces heart rate and myocardial oxygen demand"},{"id":"r3","text":"Inhibits sodium/chloride reabsorption in the loop of Henle"}],"correct_pairs":[["l1","r1"],["l2","r2"],["l3","r3"]]}',
'medium', 2.00, 'partial_credit', 'published', @instr1);
SET @q4 = LAST_INSERT_ID();

-- 5. matrix_single
INSERT INTO questions (category_id, type, question_text, question_data, difficulty, points, scoring_method, status, created_by) VALUES
(@cat_medsurg, 'matrix_single', 'For each assessment finding, indicate whether it is Expected or Concerning for this patient''s condition.',
'{"rows":[{"id":"r1","label":"SpO2 of 91% on room air"},{"id":"r2","label":"BP of 158/94 mmHg"},{"id":"r3","label":"HR of 102 bpm"}],"columns":[{"id":"c1","label":"Expected"},{"id":"c2","label":"Concerning"}],"correct":{"r1":["c2"],"r2":["c2"],"r3":["c2"]}}',
'hard', 3.00, 'partial_credit', 'published', @instr1);
SET @q5 = LAST_INSERT_ID();

-- 6. essay
INSERT INTO questions (category_id, type, question_text, question_data, difficulty, points, scoring_method, status, created_by) VALUES
(@cat_medsurg, 'essay', 'Describe the priority nursing interventions for a patient presenting with acute decompensated heart failure.',
'{}',
'medium', 3.00, 'all_or_nothing', 'published', @instr1);
SET @q6 = LAST_INSERT_ID();

-- Link questions 1, 3, 5 to the unfolding case study, in order
UPDATE questions SET case_id = @case1, case_order = 1 WHERE id = @q1;
UPDATE questions SET case_id = @case1, case_order = 2 WHERE id = @q3;
UPDATE questions SET case_id = @case1, case_order = 3 WHERE id = @q5;

-- ---------------- EXAM ----------------
-- One published exam on course1, using all 6 questions as fixed picks
INSERT INTO exams (title, description, category_id, course_id, duration_minutes, pass_percentage, shuffle_questions, shuffle_options, max_attempts, gradebook_category, status, created_by) VALUES
('Med-Surg Practice Exam 1', 'A short practice exam covering cardiovascular assessment and pharmacology basics.', @cat_medsurg, @course1, 30, 75.00, 1, 1, 3, 'summative', 'published', @instr1);
SET @exam1 = LAST_INSERT_ID();

INSERT INTO exam_questions (exam_id, question_id, order_index) VALUES
(@exam1, @q1, 1),
(@exam1, @q2, 2),
(@exam1, @q3, 3),
(@exam1, @q4, 4),
(@exam1, @q5, 5),
(@exam1, @q6, 6);

-- ---------------- GRADEBOOK ----------------
-- Auto-create a gradebook item for the exam above, weighted 100% of course1's grade
INSERT INTO gradebook_items (course_id, item_type, item_id, title, weight, max_score) VALUES
(@course1, 'quiz', @exam1, 'Med-Surg Practice Exam 1', 100.00, 12.00);
