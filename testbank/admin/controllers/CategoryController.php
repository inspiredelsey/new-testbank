<?php
/**
 * Category Controller for Test Bank LMS Admin Panel
 * Handles all Category management views and actions.
 */

require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../models/Category.php';

class CategoryController {
    private $model;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->model = new Category();
    }

    /**
     * Dispatch routing requests based on action parameter.
     * 
     * @param string $action
     */
    public function handleRequest($action) {
        switch ($action) {
            case 'list':
            case 'index':
                $this->handleList();
                break;

            case 'create':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->handleCreatePost();
                } else {
                    $this->handleCreateGet();
                }
                break;

            case 'edit':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->handleEditPost();
                } else {
                    $this->handleEditGet();
                }
                break;

            case 'delete':
                $this->handleDelete();
                break;

            default:
                header("Location: index.php?route=admin/categories&action=list");
                exit;
        }
    }

    /**
     * Action: List all categories in a hierarchical tree view
     */
    private function handleList() {
        $categoryTree = $this->model->tree();
        $totalCategoriesCount = count($this->model->all());
        $csrfToken = Session::getCSRFToken();

        require_once __DIR__ . '/../views/categories/tree.php';
    }

    /**
     * Action: Show Create Form
     */
    private function handleCreateGet() {
        $title = "Add Category";
        $submitUrl = "index.php?route=admin/categories&action=create";
        $isEdit = false;

        $errors = Session::get('validation_errors') ?? [];
        $formData = Session::get('form_data') ?? [];

        Session::delete('validation_errors');
        Session::delete('form_data');

        // Retrieve flat categories for parent dropdown selector
        $flatCategories = $this->model->getTreeFlat();
        $excludeIds = []; // No exclusions for a new category

        require_once __DIR__ . '/../views/categories/form.php';
    }

    /**
     * Action: Process Category Creation (POST)
     */
    private function handleCreatePost() {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            Session::set('form_data', $_POST);
            header("Location: index.php?route=admin/categories&action=create&error=" . urlencode("Security token validation failed. Please try again."));
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $description = trim($_POST['description'] ?? '');

        $errors = [];

        if (empty($name)) {
            $errors['name'] = "Category name is required.";
        } elseif (strlen($name) > 150) {
            $errors['name'] = "Category name must not exceed 150 characters.";
        }

        if ($parentId !== null) {
            $parent = $this->model->find($parentId);
            if (!$parent) {
                $errors['parent_id'] = "Selected parent category does not exist.";
            }
        }

        if (!empty($errors)) {
            Session::set('validation_errors', $errors);
            Session::set('form_data', $_POST);
            header("Location: index.php?route=admin/categories&action=create");
            exit;
        }

        try {
            $this->model->create([
                'name' => $name,
                'parent_id' => $parentId,
                'description' => $description
            ]);
            header("Location: index.php?route=admin/categories&action=list&success=" . urlencode("Category successfully created."));
            exit;
        } catch (Exception $e) {
            Session::set('validation_errors', ['db' => $e->getMessage()]);
            Session::set('form_data', $_POST);
            header("Location: index.php?route=admin/categories&action=create");
            exit;
        }
    }

    /**
     * Action: Show Edit Form
     */
    private function handleEditGet() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $category = $this->model->find($id);

        if (!$category) {
            header("Location: index.php?route=admin/categories&action=list&error=" . urlencode("Category not found."));
            exit;
        }

        $title = "Edit Category";
        $submitUrl = "index.php?route=admin/categories&action=edit&id=" . $category['id'];
        $isEdit = true;

        $errors = Session::get('validation_errors') ?? [];
        $formData = Session::get('form_data') ?? $category;

        Session::delete('validation_errors');
        Session::delete('form_data');

        // Exclude current category and its descendants to prevent circular reference
        $flatCategories = $this->model->getTreeFlat();
        $excludeIds = array_merge([$id], $this->model->getDescendantIds($id));

        require_once __DIR__ . '/../views/categories/form.php';
    }

    /**
     * Action: Process Category Edit (POST)
     */
    private function handleEditPost() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $category = $this->model->find($id);

        if (!$category) {
            header("Location: index.php?route=admin/categories&action=list&error=" . urlencode("Category not found."));
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            Session::set('form_data', $_POST);
            header("Location: index.php?route=admin/categories&action=edit&id=" . $id . "&error=" . urlencode("Security token validation failed. Please try again."));
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $description = trim($_POST['description'] ?? '');

        $errors = [];

        if (empty($name)) {
            $errors['name'] = "Category name is required.";
        } elseif (strlen($name) > 150) {
            $errors['name'] = "Category name must not exceed 150 characters.";
        }

        if ($parentId !== null) {
            if ($parentId === $id) {
                $errors['parent_id'] = "A category cannot be set as its own parent.";
            } else {
                $descendantIds = $this->model->getDescendantIds($id);
                if (in_array($parentId, $descendantIds)) {
                    $errors['parent_id'] = "Circular reference detected: Parent category cannot be a descendant of this category.";
                }
            }
        }

        if (!empty($errors)) {
            Session::set('validation_errors', $errors);
            $oldData = $_POST;
            $oldData['id'] = $id;
            Session::set('form_data', $oldData);
            header("Location: index.php?route=admin/categories&action=edit&id=" . $id);
            exit;
        }

        try {
            $this->model->update($id, [
                'name' => $name,
                'parent_id' => $parentId,
                'description' => $description
            ]);
            header("Location: index.php?route=admin/categories&action=list&success=" . urlencode("Category successfully updated."));
            exit;
        } catch (Exception $e) {
            Session::set('validation_errors', ['db' => $e->getMessage()]);
            $oldData = $_POST;
            $oldData['id'] = $id;
            Session::set('form_data', $oldData);
            header("Location: index.php?route=admin/categories&action=edit&id=" . $id);
            exit;
        }
    }

    /**
     * Action: Delete Category
     */
    private function handleDelete() {
        $token = $_GET['csrf_token'] ?? $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/categories&action=list&error=" . urlencode("Security token validation failed. Please try again."));
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $category = $this->model->find($id);

        if (!$category) {
            header("Location: index.php?route=admin/categories&action=list&error=" . urlencode("Category not found."));
            exit;
        }

        try {
            $this->model->delete($id);
            header("Location: index.php?route=admin/categories&action=list&success=" . urlencode("Category successfully deleted."));
            exit;
        } catch (Exception $e) {
            header("Location: index.php?route=admin/categories&action=list&error=" . urlencode($e->getMessage()));
            exit;
        }
    }
}
