<?php
/**
 * Category Controller (Admin/Instructor)
 */

require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../../includes/Auth.php';

class CategoryController {
    private $model;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->model = new Category();
    }

    public function handleRequest($action = 'index') {
        $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        
        switch ($action) {
            case 'create':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    try {
                        $this->model->create($_POST);
                        header("Location: index.php?route=admin/categories&success=Category created successfully");
                        exit;
                    } catch (Exception $e) {
                        header("Location: index.php?route=admin/categories&error=" . urlencode($e->getMessage()));
                        exit;
                    }
                }
                break;

            case 'edit':
                $id = intval($_GET['id'] ?? 0);
                $category = $this->model->getById($id);
                if (!$category) {
                    header("Location: index.php?route=admin/categories&error=Category not found");
                    exit;
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    try {
                        $this->model->update($id, $_POST);
                        header("Location: index.php?route=admin/categories&success=Category updated successfully");
                        exit;
                    } catch (Exception $e) {
                        $error = $e->getMessage();
                        $flatCategories = $this->model->getTreeFlat();
                        include __DIR__ . '/../views/categories/edit.php';
                        exit;
                    }
                }
                $flatCategories = $this->model->getTreeFlat();
                include __DIR__ . '/../views/categories/edit.php';
                exit;

            case 'delete':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['confirm'])) {
                    if (!Session::validateCSRF($csrfToken)) {
                        $this->renderError("CSRF validation failed.");
                    }
                    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
                    $this->model->delete($id);
                    header("Location: index.php?route=admin/categories&success=Category deleted successfully");
                    exit;
                }
                break;

            case 'index':
            default:
                $flatCategories = $this->model->getTreeFlat();
                $categoryTree = $this->model->getTree();
                include __DIR__ . '/../views/categories/index.php';
                exit;
        }
    }

    private function renderError($msg) {
        http_response_code(400);
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
        echo "<h2>Error</h2>";
        echo "<p>" . htmlspecialchars($msg) . "</p>";
        echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
        echo "</div>";
        exit;
    }
}
