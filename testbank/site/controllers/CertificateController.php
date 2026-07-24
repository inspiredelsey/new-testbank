<?php
/**
 * Student Certificate Controller
 */

require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Database.php';

class CertificateController {

    public function __construct() {
        Auth::requireLogin();
    }

    /**
     * Dispatch routing requests based on action parameter.
     */
    public function handleRequest($action = 'mycertificates') {
        switch ($action) {
            case 'mycertificates':
            case 'list':
                $this->handleMyCertificates();
                break;

            case 'download':
                $this->handleDownload();
                break;

            default:
                header("Location: index.php?route=student/dashboard");
                break;
        }
    }

    /**
     * Show all certificates earned by the student
     */
    private function handleMyCertificates() {
        $user = Auth::user();
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT c.*, co.title as course_title
            FROM certificates c
            JOIN courses co ON c.course_id = co.id
            WHERE c.user_id = ?
            ORDER BY c.issued_at DESC
        ");
        $stmt->execute([$user['id']]);
        $certificates = $stmt->fetchAll() ?: [];

        include __DIR__ . '/../views/certificates/mycertificates.php';
    }

    /**
     * Download the certificate PDF securely
     */
    private function handleDownload() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $user = Auth::user();
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM certificates WHERE id = ?");
        $stmt->execute([$id]);
        $cert = $stmt->fetch();

        if (!$cert) {
            http_response_code(404);
            echo "Certificate not found.";
            exit;
        }

        // Verify ownership: must belong to the logged-in student
        if ((int)$cert['user_id'] !== (int)$user['id']) {
            http_response_code(403);
            echo "Access Denied: You do not have permission to view or download this certificate.";
            exit;
        }

        $fullPath = __DIR__ . '/../../../' . $cert['pdf_path'];
        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo "Certificate PDF file not found on disk.";
            exit;
        }

        // Stream PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="my_certificate_' . $id . '.pdf"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }
}
