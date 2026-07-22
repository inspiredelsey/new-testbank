<?php
/**
 * Certificate Template Model
 */

class CertificateTemplate {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Find template by course ID.
     */
    public function find($courseId) {
        $stmt = $this->db->prepare("SELECT * FROM certificate_templates WHERE course_id = ?");
        $stmt->execute([$courseId]);
        return $stmt->fetch();
    }

    /**
     * Create or update a certificate template (upsert).
     */
    public function createOrUpdate($courseId, $data) {
        $existing = $this->find($courseId);

        $title = $data['title'] ?? 'Certificate of Completion';
        $content = $data['content'] ?? $data['html_template'] ?? '';
        $htmlTemplate = $data['html_template'] ?? $content;
        $backgroundImage = $data['background_image'] ?? ($existing ? $existing['background_image'] : null);

        if ($existing) {
            $stmt = $this->db->prepare("
                UPDATE certificate_templates 
                SET title = ?, content = ?, html_template = ?, background_image = ?
                WHERE course_id = ?
            ");
            return $stmt->execute([$title, $content, $htmlTemplate, $backgroundImage, $courseId]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO certificate_templates (course_id, title, content, html_template, background_image)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$courseId, $title, $content, $htmlTemplate, $backgroundImage]);
        }
    }
}
