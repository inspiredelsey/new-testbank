-- Migration: de-duplicate certificates.student_id/user_id and
-- certificates.certificate_number/certificate_code, and drop
-- certificate_templates.content (superseded by html_template).
--
-- Safe to run once against an EXISTING database that still has the
-- duplicated columns from before this fix. Run this BEFORE applying
-- a fresh copy of sql/schema.sql, or skip it entirely on a brand-new
-- database created from the already-corrected schema.sql.
--
-- BACK UP YOUR DATABASE BEFORE RUNNING THIS.

-- 1. Consolidate data into the canonical columns before dropping anything.
UPDATE certificates SET user_id = student_id WHERE user_id IS NULL AND student_id IS NOT NULL;
UPDATE certificates SET certificate_number = certificate_code WHERE certificate_number IS NULL AND certificate_code IS NOT NULL;

-- 2. Drop the old unique indexes that reference the columns being removed.
--    (These are the exact index names from the pre-fix schema; if your
--    database has different auto-generated names, check with
--    SHOW INDEX FROM certificates; and adjust accordingly.)
--    NOTE: "DROP INDEX IF EXISTS" is supported on MariaDB 10.1.4+ and
--    MySQL 8.0.29+. If your server is older and this errors, just remove
--    "IF EXISTS" and re-run, or skip these two lines if the index is
--    already absent — it's not fatal to the rest of the migration.
DROP INDEX IF EXISTS idx_cert_course_student ON certificates;
DROP INDEX IF EXISTS idx_cert_student ON certificates;

-- 3. Drop the foreign key on student_id. MySQL auto-generates the
--    constraint name if one wasn't explicitly given, so this looks it
--    up dynamically rather than assuming a fixed name.
SET @fk_name := (
  SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'certificates'
    AND COLUMN_NAME = 'student_id'
    AND REFERENCED_TABLE_NAME IS NOT NULL
  LIMIT 1
);
SET @drop_fk_sql := IF(@fk_name IS NOT NULL,
  CONCAT('ALTER TABLE certificates DROP FOREIGN KEY `', @fk_name, '`'),
  'SELECT 1'
);
PREPARE stmt FROM @drop_fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Now safe to drop the redundant columns.
ALTER TABLE certificates
  DROP COLUMN student_id,
  DROP COLUMN certificate_code,
  MODIFY COLUMN user_id INT NOT NULL;

-- 5. Re-add the canonical unique constraint (course_id, user_id) if not already present.
ALTER TABLE certificates
  ADD UNIQUE KEY idx_cert_course_user (course_id, user_id);

-- 6. Drop the redundant content column from certificate_templates
--    (copy any data into html_template first, in case it was used instead).
UPDATE certificate_templates SET html_template = content WHERE (html_template IS NULL OR html_template = '') AND content IS NOT NULL;
ALTER TABLE certificate_templates DROP COLUMN content;
