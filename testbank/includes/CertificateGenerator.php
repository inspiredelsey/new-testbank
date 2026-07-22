<?php
/**
 * Certificate Generator Helper
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GradebookCalculator.php';

// Safe load Composer autoloader
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

// Define common CURL constants to prevent undefined constant errors on systems without ext-curl
if (!defined('CURLOPT_CONNECTTIMEOUT')) define('CURLOPT_CONNECTTIMEOUT', 78);
if (!defined('CURLOPT_TIMEOUT')) define('CURLOPT_TIMEOUT', 13);
if (!defined('CURLOPT_RETURNTRANSFER')) define('CURLOPT_RETURNTRANSFER', 19913);
if (!defined('CURLOPT_URL')) define('CURLOPT_URL', 10002);
if (!defined('CURLOPT_HEADER')) define('CURLOPT_HEADER', 42);
if (!defined('CURLOPT_USERAGENT')) define('CURLOPT_USERAGENT', 10018);
if (!defined('CURLOPT_SSL_VERIFYPEER')) define('CURLOPT_SSL_VERIFYPEER', 64);
if (!defined('CURLOPT_SSL_VERIFYHOST')) define('CURLOPT_SSL_VERIFYHOST', 81);
if (!defined('CURLOPT_FOLLOWLOCATION')) define('CURLOPT_FOLLOWLOCATION', 52);
if (!defined('CURLOPT_MAXREDIRS')) define('CURLOPT_MAXREDIRS', 68);
if (!defined('CURLOPT_HTTPHEADER')) define('CURLOPT_HTTPHEADER', 10023);
if (!defined('CURL_HTTP_VERSION_1_1')) define('CURL_HTTP_VERSION_1_1', 1);
if (!defined('CURLOPT_HTTP_VERSION')) define('CURLOPT_HTTP_VERSION', 84);
if (!defined('CURLOPT_PROTOCOLS')) define('CURLOPT_PROTOCOLS', 181);
if (!defined('CURL_REDIR_PROTOCOLS')) define('CURL_REDIR_PROTOCOLS', 182);
if (!defined('CURLOPT_REDIR_PROTOCOLS')) define('CURLOPT_REDIR_PROTOCOLS', 182);
if (!defined('CURLPROTO_HTTP')) define('CURLPROTO_HTTP', 1);
if (!defined('CURLPROTO_HTTPS')) define('CURLPROTO_HTTPS', 2);
if (!defined('CURLPROTO_FTP')) define('CURLPROTO_FTP', 4);
if (!defined('CURLPROTO_FTPS')) define('CURLPROTO_FTPS', 8);
if (!defined('CURLINFO_HTTP_CODE')) define('CURLINFO_HTTP_CODE', 2097154);
if (!defined('CURLOPT_FAILONERROR')) define('CURLOPT_FAILONERROR', 19);

class CertificateGenerator {

    /**
     * Checks if a student is eligible for a certificate and issues it if so.
     * Safe to call repeatedly without creating duplicates.
     */
    public static function checkAndIssue($userId, $courseId) {
        $db = Database::getInstance()->getConnection();

        // 1. Check if a certificate already exists for this user+course
        $stmtCheck = $db->prepare("SELECT id FROM certificates WHERE course_id = ? AND (student_id = ? OR user_id = ?)");
        $stmtCheck->execute([$courseId, $userId, $userId]);
        $existingId = $stmtCheck->fetchColumn();

        if ($existingId) {
            return $existingId;
        }

        // 2. Load course details (pass_percentage)
        $stmtCourse = $db->prepare("SELECT pass_percentage FROM courses WHERE id = ?");
        $stmtCourse->execute([$courseId]);
        $course = $stmtCourse->fetch();

        if (!$course) {
            return false;
        }

        $passPercentage = floatval($course['pass_percentage'] ?? 70.00);

        // 3. Compute final grade
        $gradeData = GradebookCalculator::finalGrade($userId, $courseId);

        // Conditions: Complete grade (not partial, has items) AND meets/exceeds pass_percentage
        if (!$gradeData['is_partial'] && $gradeData['total_items'] > 0 && $gradeData['final_grade'] >= $passPercentage) {
            // Issue the certificate!
            $cert = self::generatePdf($courseId, $userId);
            if ($cert) {
                require_once __DIR__ . '/ActivityLogger.php';
                ActivityLogger::log($userId, 'certificate_issued', $courseId, 'certificate', $cert['id']);
                return $cert['id'];
            }
            return false;
        }

        return false;
    }

    /**
     * Renders a certificate template with token values and returns the compiled HTML content and background path.
     */
    private static function renderTemplate($courseId, $tokenValues) {
        $db = Database::getInstance()->getConnection();

        // Load the template
        $stmtTemplate = $db->prepare("SELECT * FROM certificate_templates WHERE course_id = ?");
        $stmtTemplate->execute([$courseId]);
        $template = $stmtTemplate->fetch();

        $htmlContent = null;
        if ($template) {
            $htmlContent = !empty($template['html_template']) ? $template['html_template'] : (!empty($template['content']) ? $template['content'] : null);
        }

        if (!$htmlContent) {
            $htmlContent = self::getDefaultTemplate();
        }

        // Token substitution with HTML escaping
        $tokens = [
            '{{student_name}}' => htmlspecialchars($tokenValues['student_name'] ?? 'Student', ENT_QUOTES, 'UTF-8'),
            '{{course_title}}' => htmlspecialchars($tokenValues['course_title'] ?? 'Course', ENT_QUOTES, 'UTF-8'),
            '{{completion_date}}' => htmlspecialchars($tokenValues['completion_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'),
            '{{certificate_number}}' => htmlspecialchars($tokenValues['certificate_number'] ?? 'PREVIEW-0000', ENT_QUOTES, 'UTF-8'),
            '{{final_grade}}' => htmlspecialchars($tokenValues['final_grade'] ?? '100', ENT_QUOTES, 'UTF-8')
        ];
        $htmlContent = str_replace(array_keys($tokens), array_values($tokens), $htmlContent);

        return [
            'html' => $htmlContent,
            'template' => $template
        ];
    }

    /**
     * Generates a preview Certificate PDF using sample placeholder data and streams it back.
     */
    public static function previewPdf($courseId) {
        $db = Database::getInstance()->getConnection();

        // Load Course
        $stmtCourse = $db->prepare("SELECT * FROM courses WHERE id = ?");
        $stmtCourse->execute([$courseId]);
        $course = $stmtCourse->fetch();

        $courseTitle = $course ? $course['title'] : 'Sample Course Title';
        $passPercentage = $course ? floatval($course['pass_percentage'] ?? 70.00) : 70.00;
        // Representative grade just above pass percentage
        $sampleGrade = max(85.00, $passPercentage);

        $tokenValues = [
            'student_name' => 'Jane Sample Student',
            'course_title' => $courseTitle,
            'completion_date' => date('Y-m-d'),
            'certificate_number' => 'PREVIEW-0000',
            'final_grade' => $sampleGrade
        ];

        $rendered = self::renderTemplate($courseId, $tokenValues);
        $htmlContent = $rendered['html'];
        $template = $rendered['template'];

        // PDF setup and generation using TCPDF
        if (!class_exists('TCPDF')) {
            error_log("TCPDF class not found. Cannot generate PDF.");
            return false;
        }

        // Create PDF
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('LMS');
        $pdf->SetAuthor('LMS');
        $pdf->SetTitle('Certificate Preview');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // Optional Background Image
        if ($template && !empty($template['background_image'])) {
            $bgPath = __DIR__ . '/../../' . $template['background_image'];
            if (file_exists($bgPath)) {
                $w = $pdf->getPageWidth();
                $h = $pdf->getPageHeight();
                // Draw background image spanning full A4 page
                $pdf->Image($bgPath, 0, 0, $w, $h, '', '', '', false, 300, '', false, false, 0);
            }
        }

        // Render HTML content
        $pdf->writeHTML($htmlContent, true, false, true, false, '');

        // Output and stream directly
        if (ob_get_length()) {
            ob_clean();
        }

        $pdf->Output('certificate_preview.pdf', 'I');
        exit;
    }

    /**
     * Generates a Certificate PDF and inserts/updates the certificate record.
     */
    public static function generatePdf($courseId, $userId) {
        $db = Database::getInstance()->getConnection();

        // Load Course
        $stmtCourse = $db->prepare("SELECT * FROM courses WHERE id = ?");
        $stmtCourse->execute([$courseId]);
        $course = $stmtCourse->fetch();

        // Load User
        $stmtUser = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch();

        if (!$course || !$user) {
            return false;
        }

        // Get final grade
        $gradeData = GradebookCalculator::finalGrade($userId, $courseId);
        $finalGrade = $gradeData['final_grade'];

        // Generate custom unique certificate number
        $certificateNumber = 'CERT-' . str_pad($courseId, 3, '0', STR_PAD_LEFT) . '-' . str_pad($userId, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(bin2hex(random_bytes(4)));

        // Check if certificate already exists
        $stmtCheck = $db->prepare("SELECT * FROM certificates WHERE course_id = ? AND (student_id = ? OR user_id = ?)");
        $stmtCheck->execute([$courseId, $userId, $userId]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            // Keep the old certificate number when regenerating
            $certificateNumber = $existing['certificate_number'] ?: $existing['certificate_code'] ?: $certificateNumber;
        }

        $tokenValues = [
            'student_name' => $user['name'] ?? 'Student',
            'course_title' => $course['title'] ?? 'Course',
            'completion_date' => date('Y-m-d'),
            'certificate_number' => $certificateNumber,
            'final_grade' => $finalGrade
        ];

        $rendered = self::renderTemplate($courseId, $tokenValues);
        $htmlContent = $rendered['html'];
        $template = $rendered['template'];

        // PDF setup and generation using TCPDF with fallback
        $dir = __DIR__ . '/../../uploads/certificates/' . $courseId;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $pdfFilename = $userId . '.pdf';
        $fullPdfPath = $dir . '/' . $pdfFilename;
        $relativePdfPath = 'uploads/certificates/' . $courseId . '/' . $pdfFilename;

        $generatedPdf = false;
        if (class_exists('TCPDF')) {
            try {
                $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
                $pdf->SetCreator('LMS');
                $pdf->SetAuthor('LMS');
                $pdf->SetTitle('Certificate of Completion');
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                $pdf->SetMargins(15, 15, 15);
                $pdf->SetAutoPageBreak(false);
                $pdf->AddPage();

                if ($template && !empty($template['background_image'])) {
                    $bgPath = __DIR__ . '/../../' . $template['background_image'];
                    if (file_exists($bgPath)) {
                        $w = $pdf->getPageWidth();
                        $h = $pdf->getPageHeight();
                        $pdf->Image($bgPath, 0, 0, $w, $h, '', '', '', false, 300, '', false, false, 0);
                    }
                }

                $pdf->writeHTML($htmlContent, true, false, true, false, '');
                $pdf->Output($fullPdfPath, 'F');
                $generatedPdf = true;
            } catch (\Throwable $e) {
                error_log("TCPDF PDF generation error: " . $e->getMessage());
                $generatedPdf = false;
            }
        }

        if (!$generatedPdf) {
            file_put_contents($fullPdfPath, $htmlContent);
        }

        // Insert/Update Certificates table
        if ($existing) {
            $stmtUpdate = $db->prepare("
                UPDATE certificates 
                SET pdf_path = ?, issued_at = CURRENT_TIMESTAMP, certificate_number = ?, certificate_code = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([$relativePdfPath, $certificateNumber, $certificateNumber, $existing['id']]);

            return [
                'id' => $existing['id'],
                'course_id' => $courseId,
                'student_id' => $userId,
                'user_id' => $userId,
                'certificate_code' => $certificateNumber,
                'certificate_number' => $certificateNumber,
                'pdf_path' => $relativePdfPath,
                'issued_at' => date('Y-m-d H:i:s')
            ];
        } else {
            $stmtInsert = $db->prepare("
                INSERT INTO certificates (course_id, student_id, user_id, certificate_code, certificate_number, pdf_path, issued_at)
                VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmtInsert->execute([
                $courseId,
                $userId,
                $userId,
                $certificateNumber,
                $certificateNumber,
                $relativePdfPath
            ]);
            $insertId = $db->lastInsertId();

            return [
                'id' => $insertId,
                'course_id' => $courseId,
                'student_id' => $userId,
                'user_id' => $userId,
                'certificate_code' => $certificateNumber,
                'certificate_number' => $certificateNumber,
                'pdf_path' => $relativePdfPath,
                'issued_at' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Elegant default HTML template for certificates.
     */
    public static function getDefaultTemplate() {
        return '
        <div style="border: 8px solid #2C3E50; padding: 30px; text-align: center; font-family: helvetica, sans-serif; background-color: #FDFFFF; color: #333;">
            <div style="border: 2px solid #2C3E50; padding: 25px;">
                <h1 style="font-size: 36px; margin-bottom: 10px; color: #2C3E50; font-weight: bold; text-transform: uppercase;">Certificate of Completion</h1>
                <p style="font-size: 16px; font-style: italic; margin-bottom: 25px; color: #7F8C8D;">This is proudly presented to</p>
                <h2 style="font-size: 28px; border-bottom: 1px solid #BDC3C7; padding-bottom: 5px; margin-bottom: 25px; color: #34495E; font-weight: bold;">{{student_name}}</h2>
                <p style="font-size: 16px; margin-bottom: 10px; color: #7F8C8D;">for successfully completing the course</p>
                <h3 style="font-size: 22px; margin-bottom: 25px; color: #2C3E50; font-weight: bold;">{{course_title}}</h3>
                <p style="font-size: 16px; margin-bottom: 30px; color: #7F8C8D;">with a final weighted grade of <strong style="color: #27AE60;">{{final_grade}}%</strong></p>
                
                <table style="width: 100%; border: none; margin-top: 20px;">
                    <tr>
                        <td style="width: 50%; text-align: left; font-size: 11px; color: #7F8C8D; line-height: 1.4;">
                            <strong>Date Issued:</strong> {{completion_date}}<br />
                            <strong>Verification Code:</strong> {{certificate_number}}
                        </td>
                        <td style="width: 50%; text-align: right; vertical-align: bottom;">
                            <span style="font-size: 14px; font-weight: bold; color: #34495E; border-top: 1px solid #BDC3C7; padding-top: 3px;">Learning Management System</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>';
    }
}
