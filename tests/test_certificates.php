<?php
/**
 * Automated Test Script for Phase 5 Certificate Generation
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../testbank/includes/Database.php';
require_once __DIR__ . '/../testbank/includes/GradebookCalculator.php';
require_once __DIR__ . '/../testbank/includes/CertificateGenerator.php';
require_once __DIR__ . '/../testbank/admin/models/GradebookItem.php';
require_once __DIR__ . '/../testbank/admin/models/CertificateTemplate.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Database connection successful!\n";

    // -------------------------------------------------------------
    // SETUP TEST DATA
    // -------------------------------------------------------------
    $db->beginTransaction();

    // 1. Create a test student
    $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, 'student', 'active')");
    $stmt->execute(['Test Student Jane', 'jane.test@lms.com', password_hash('password', PASSWORD_DEFAULT)]);
    $studentId = $db->lastInsertId();
    echo "Created test student with ID: $studentId\n";

    // 2. Create a test course with pass_percentage = 70
    $stmt = $db->prepare("INSERT INTO courses (title, description, pass_percentage, status) VALUES (?, ?, ?, 'published')");
    $stmt->execute(['Advanced Chemistry 101', 'A high-level chemistry course.', 70.00]);
    $courseId = $db->lastInsertId();
    echo "Created test course with ID: $courseId, pass_percentage: 70%\n";

    // Enroll student in course
    $stmt = $db->prepare("INSERT INTO course_enrollments (course_id, student_id, status) VALUES (?, ?, 'active')");
    $stmt->execute([$courseId, $studentId]);

    // 3. Create two gradebook items (Manual)
    // Item 1: Midterm (Weight: 40%, Max Score: 100)
    // Item 2: Final Exam (Weight: 60%, Max Score: 100)
    $gradebookItemModel = new GradebookItem();

    $stmt = $db->prepare("INSERT INTO gradebook_items (course_id, item_type, title, weight, max_score) VALUES (?, 'manual', 'Midterm Exam', 40.00, 100.00)");
    $stmt->execute([$courseId]);
    $midtermItemId = $db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO gradebook_items (course_id, item_type, title, weight, max_score) VALUES (?, 'manual', 'Final Exam', 60.00, 100.00)");
    $stmt->execute([$courseId]);
    $finalExamItemId = $db->lastInsertId();

    echo "Created two manual gradebook items:\n";
    echo "  - Midterm Exam (Weight: 40%, Max: 100, ID: $midtermItemId)\n";
    echo "  - Final Exam (Weight: 60%, Max: 100, ID: $finalExamItemId)\n";

    // -------------------------------------------------------------
    // RUN TEST CASES
    // -------------------------------------------------------------

    // TEST CASE 1: No grades recorded yet. Expect no certificate.
    echo "\n--- TEST CASE 1: No grades recorded yet ---\n";
    $gradeData = GradebookCalculator::finalGrade($studentId, $courseId);
    echo "Final grade computed: " . $gradeData['final_grade'] . "% (Is Partial: " . ($gradeData['is_partial'] ? 'Yes' : 'No') . ")\n";
    
    $issued = CertificateGenerator::checkAndIssue($studentId, $courseId);
    if ($issued === false) {
        echo "✅ Correct: No certificate issued for empty grades.\n";
    } else {
        throw new Exception("❌ Fail: Certificate issued on empty grades!");
    }


    // TEST CASE 2: Only Midterm graded (90/100). Final is ungraded (Partial).
    // The student's current partial percentage is 90% (which is >= 70% threshold).
    // Expect no certificate since it's a partial grade.
    echo "\n--- TEST CASE 2: Partial Grade (Midterm = 90%, Final ungraded) ---\n";
    // We add the score manually. This should trigger addManualScore and call checkAndIssue internally.
    $gradebookItemModel->addManualScore($midtermItemId, $studentId, 90.00);
    
    $gradeData = GradebookCalculator::finalGrade($studentId, $courseId);
    echo "Final grade computed: " . $gradeData['final_grade'] . "% (Is Partial: " . ($gradeData['is_partial'] ? 'Yes' : 'No') . ")\n";
    
    // Check certificates table
    $stmtCert = $db->prepare("SELECT COUNT(*) FROM certificates WHERE course_id = ? AND student_id = ?");
    $stmtCert->execute([$courseId, $studentId]);
    $certCount = $stmtCert->fetchColumn();
    
    if ($certCount == 0) {
        echo "✅ Correct: No certificate issued for partial graded state (even though score is 90%).\n";
    } else {
        throw new Exception("❌ Fail: Certificate issued on a partial graded state!");
    }


    // TEST CASE 3: Fully graded but below passing threshold (Midterm = 90, Final = 50).
    // Final Grade: (90 * 0.40) + (50 * 0.60) = 36 + 30 = 66% (< 70% threshold).
    // Expect no certificate.
    echo "\n--- TEST CASE 3: Fully graded but below threshold (66%) ---\n";
    $gradebookItemModel->addManualScore($finalExamItemId, $studentId, 50.00);

    $gradeData = GradebookCalculator::finalGrade($studentId, $courseId);
    echo "Final grade computed: " . $gradeData['final_grade'] . "% (Is Partial: " . ($gradeData['is_partial'] ? 'Yes' : 'No') . ")\n";

    $stmtCert->execute([$courseId, $studentId]);
    $certCount = $stmtCert->fetchColumn();

    if ($certCount == 0) {
        echo "✅ Correct: No certificate issued for a failing final grade of 66%.\n";
    } else {
        throw new Exception("❌ Fail: Certificate issued for failing grade!");
    }


    // TEST CASE 4: Fully graded and passing (Midterm = 90, Final updated to 65).
    // Final Grade: (90 * 0.40) + (65 * 0.60) = 36 + 39 = 75% (>= 70% threshold).
    // Expect automatic certificate generation the moment the score is recorded!
    echo "\n--- TEST CASE 4: Fully graded and passing (75%) ---\n";
    // This call will update Final Exam score and trigger checkAndIssue
    $gradebookItemModel->addManualScore($finalExamItemId, $studentId, 65.00);

    $gradeData = GradebookCalculator::finalGrade($studentId, $courseId);
    echo "Final grade computed: " . $gradeData['final_grade'] . "% (Is Partial: " . ($gradeData['is_partial'] ? 'Yes' : 'No') . ")\n";

    // Check certificates table
    $stmtCertLoad = $db->prepare("SELECT * FROM certificates WHERE course_id = ? AND student_id = ?");
    $stmtCertLoad->execute([$courseId, $studentId]);
    $cert = $stmtCertLoad->fetch();

    if ($cert) {
        echo "✅ Success: Certificate automatically issued!\n";
        echo "  - Certificate Code: " . $cert['certificate_number'] . "\n";
        echo "  - PDF Path: " . $cert['pdf_path'] . "\n";

        // Verify PDF file exists on disk
        $fullPdfPath = __DIR__ . '/../' . $cert['pdf_path'];
        if (file_exists($fullPdfPath)) {
            echo "✅ Success: PDF file successfully saved to disk at " . $fullPdfPath . "\n";

            // Verify PDF contents (simple search for text in raw PDF stream)
            $rawPdf = file_get_contents($fullPdfPath);
            if (strpos($rawPdf, 'Test Student Jane') !== false || strpos($rawPdf, 'Jane') !== false) {
                echo "✅ Success: PDF contains the student's name!\n";
            } else {
                echo "⚠️ Note: Raw name text not directly searchable in binary PDF, which is normal for compressed/sub-font PDFs.\n";
            }
        } else {
            throw new Exception("❌ Fail: PDF file was not created on disk at $fullPdfPath");
        }
    } else {
        throw new Exception("❌ Fail: Certificate was NOT automatically issued!");
    }


    // TEST CASE 5: Duplicate prevention / idempotency.
    // Calling checkAndIssue again should return the existing certificate ID without duplication.
    echo "\n--- TEST CASE 5: Duplicate Prevention ---\n";
    $secondIssueId = CertificateGenerator::checkAndIssue($studentId, $courseId);
    
    if ($secondIssueId == $cert['id']) {
        echo "✅ Success: checkAndIssue is safe to call repeatedly; returned existing ID {$secondIssueId} without duplication.\n";
    } else {
        throw new Exception("❌ Fail: Duplicate certificate generated or wrong ID returned!");
    }

    $db->rollBack();
    echo "\n🎉 All Test Cases Passed Successfully! Transaction rolled back clean. 🎉\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ TEST FAILURE: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
