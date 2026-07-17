<?php
/**
 * User Controller for Test Bank LMS Admin Panel
 * Handles all User management views and actions.
 */

require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $userModel;

    public function __construct() {
        Auth::requireRole('admin');
        $this->userModel = new User();
    }

    /**
     * Dispatching routing request based on the action parameter.
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

            case 'toggle':
                $this->handleToggleStatus();
                break;

            case 'delete':
                $this->handleDelete();
                break;

            default:
                header("Location: index.php?route=admin/users&action=list");
                exit;
        }
    }

    /**
     * Action: List Users
     */
    private function handleList() {
        $roleFilter = !empty($_GET['role']) ? $_GET['role'] : null;
        $statusFilter = !empty($_GET['status']) ? $_GET['status'] : null;

        if ($roleFilter && !in_array($roleFilter, ['admin', 'instructor', 'student'])) {
            $roleFilter = null;
        }
        if ($statusFilter && !in_array($statusFilter, ['active', 'disabled'])) {
            $statusFilter = null;
        }

        $users = $this->userModel->all($roleFilter, $statusFilter);
        $csrfToken = Session::getCSRFToken();

        require_once __DIR__ . '/../views/users/list.php';
    }

    /**
     * Action: Show Create Form
     */
    private function handleCreateGet() {
        $title = "Add User";
        $submitUrl = "index.php?route=admin/users&action=create";
        
        $errors = Session::get('validation_errors') ?? [];
        $formData = Session::get('form_data') ?? [];
        
        Session::delete('validation_errors');
        Session::delete('form_data');

        require_once __DIR__ . '/../views/users/form.php';
    }

    /**
     * Action: Process Create Form (POST)
     */
    private function handleCreatePost() {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            Session::set('form_data', $_POST);
            header("Location: index.php?route=admin/users&action=create&error=Security token validation failed. Please try again.");
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';
        $status = trim($_POST['status'] ?? 'active');

        $errors = [];

        if (empty($name)) {
            $errors['name'] = 'Name is required.';
        } elseif (strlen($name) > 150) {
            $errors['name'] = 'Name cannot exceed 150 characters.';
        }

        if (empty($email)) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        } else {
            $existing = $this->userModel->findByEmail($email);
            if ($existing) {
                $errors['email'] = 'This email address is already registered.';
            }
        }

        if (empty($role) || !in_array($role, ['admin', 'instructor', 'student'])) {
            $errors['role'] = 'Please select a valid role.';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long.';
        }

        if (!in_array($status, ['active', 'disabled'])) {
            $status = 'active';
        }

        if (!empty($errors)) {
            Session::set('validation_errors', $errors);
            Session::set('form_data', $_POST);
            header("Location: index.php?route=admin/users&action=create");
            exit;
        }

        $createSuccess = $this->userModel->create([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'password' => $password,
            'status' => $status
        ]);

        if ($createSuccess) {
            header("Location: index.php?route=admin/users&action=list&success=User successfully created.");
        } else {
            Session::set('form_data', $_POST);
            header("Location: index.php?route=admin/users&action=create&error=An error occurred while creating the user.");
        }
        exit;
    }

    /**
     * Action: Show Edit Form
     */
    private function handleEditGet() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $user = $this->userModel->find($id);

        if (!$user) {
            header("Location: index.php?route=admin/users&action=list&error=User not found.");
            exit;
        }

        $title = "Edit User";
        $submitUrl = "index.php?route=admin/users&action=edit&id=" . $user['id'];

        $errors = Session::get('validation_errors') ?? [];
        $formData = Session::get('form_data') ?? $user;

        Session::delete('validation_errors');
        Session::delete('form_data');

        require_once __DIR__ . '/../views/users/form.php';
    }

    /**
     * Action: Process Edit Form (POST)
     */
    private function handleEditPost() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $user = $this->userModel->find($id);

        if (!$user) {
            header("Location: index.php?route=admin/users&action=list&error=User not found.");
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            Session::set('form_data', $_POST);
            header("Location: index.php?route=admin/users&action=edit&id=" . $id . "&error=Security token validation failed. Please try again.");
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';
        $status = trim($_POST['status'] ?? 'active');

        $errors = [];

        if (empty($name)) {
            $errors['name'] = 'Name is required.';
        } elseif (strlen($name) > 150) {
            $errors['name'] = 'Name cannot exceed 150 characters.';
        }

        if (empty($email)) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        } else {
            $existing = $this->userModel->findByEmail($email);
            if ($existing && (int)$existing['id'] !== $id) {
                $errors['email'] = 'This email address is already registered by another user.';
            }
        }

        if (empty($role) || !in_array($role, ['admin', 'instructor', 'student'])) {
            $errors['role'] = 'Please select a valid role.';
        }

        if (!empty($password) && strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long.';
        }

        if (!in_array($status, ['active', 'disabled'])) {
            $status = 'active';
        }

        $currentLoggedIn = Auth::getUser();
        if ($currentLoggedIn && (int)$currentLoggedIn['id'] === $id && $status === 'disabled') {
            $errors['status'] = 'You cannot disable your own currently logged-in account.';
        }

        if (!empty($errors)) {
            Session::set('validation_errors', $errors);
            $oldData = $_POST;
            $oldData['id'] = $id;
            Session::set('form_data', $oldData);
            header("Location: index.php?route=admin/users&action=edit&id=" . $id);
            exit;
        }

        $updateData = [
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'status' => $status
        ];

        if (!empty($password)) {
            $updateData['password'] = $password;
        }

        $updateSuccess = $this->userModel->update($id, $updateData);

        if ($updateSuccess) {
            header("Location: index.php?route=admin/users&action=list&success=User successfully updated.");
        } else {
            $oldData = $_POST;
            $oldData['id'] = $id;
            Session::set('form_data', $oldData);
            header("Location: index.php?route=admin/users&action=edit&id=" . $id . "&error=An error occurred while updating the user.");
        }
        exit;
    }

    /**
     * Action: Toggle user status (active <-> disabled)
     */
    private function handleToggleStatus() {
        $token = $_GET['csrf_token'] ?? $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/users&action=list&error=Security token validation failed. Please try again.");
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $user = $this->userModel->find($id);

        if (!$user) {
            header("Location: index.php?route=admin/users&action=list&error=User not found.");
            exit;
        }

        $currentLoggedIn = Auth::getUser();
        if ($currentLoggedIn && (int)$currentLoggedIn['id'] === $id) {
            header("Location: index.php?route=admin/users&action=list&error=You cannot disable your own currently logged-in account.");
            exit;
        }

        $newStatus = ($user['status'] === 'active') ? 'disabled' : 'active';
        $success = $this->userModel->setStatus($id, $newStatus);

        if ($success) {
            $statusWord = ($newStatus === 'active') ? 'enabled' : 'disabled';
            header("Location: index.php?route=admin/users&action=list&success=User account successfully {$statusWord}.");
        } else {
            header("Location: index.php?route=admin/users&action=list&error=Failed to update user status.");
        }
        exit;
    }

    /**
     * Action: Delete User (Soft Delete / Disable)
     */
    private function handleDelete() {
        $token = $_GET['csrf_token'] ?? $_POST['csrf_token'] ?? '';
        if (!Session::validateCSRF($token)) {
            header("Location: index.php?route=admin/users&action=list&error=Security token validation failed. Please try again.");
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $user = $this->userModel->find($id);

        if (!$user) {
            header("Location: index.php?route=admin/users&action=list&error=User not found.");
            exit;
        }

        $currentLoggedIn = Auth::getUser();
        if ($currentLoggedIn && (int)$currentLoggedIn['id'] === $id) {
            header("Location: index.php?route=admin/users&action=list&error=You cannot delete your own currently logged-in account.");
            exit;
        }

        $success = $this->userModel->delete($id);

        if ($success) {
            header("Location: index.php?route=admin/users&action=list&success=User account successfully deleted (soft-deleted / disabled).");
        } else {
            header("Location: index.php?route=admin/users&action=list&error=Failed to delete user account.");
        }
        exit;
    }
}
