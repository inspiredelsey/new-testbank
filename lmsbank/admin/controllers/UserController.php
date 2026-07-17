<?php
/**
 * User Controller for LMS Bank Admin Panel
 * Handles all User management views and actions.
 */

require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Csrf.php';
require_once __DIR__ . '/../models/User.php';

Session::start();

// Strict Access Control: Admin only
Auth::requireRole('admin');

$currentUser = Auth::user();
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        handleList();
        break;

    case 'create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handleCreatePost();
        } else {
            handleCreateGet();
        }
        break;

    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handleEditPost();
        } else {
            handleEditGet();
        }
        break;

    case 'toggle':
        handleToggleStatus();
        break;

    case 'delete':
        handleDelete();
        break;

    default:
        header("Location: UserController.php?action=list");
        exit;
}

/**
 * Action: List Users
 */
function handleList() {
    $roleFilter = !empty($_GET['role']) ? $_GET['role'] : null;
    $statusFilter = !empty($_GET['status']) ? $_GET['status'] : null;

    // Validate query parameter values to prevent issues
    if ($roleFilter && !in_array($roleFilter, ['admin', 'instructor', 'student'])) {
        $roleFilter = null;
    }
    if ($statusFilter && !in_array($statusFilter, ['active', 'disabled'])) {
        $statusFilter = null;
    }

    $users = User::all($roleFilter, $statusFilter);

    // Render list view
    require_once __DIR__ . '/../views/users/list.php';
}

/**
 * Action: Show Create Form
 */
function handleCreateGet() {
    $title = "Add User";
    $submitUrl = "UserController.php?action=create";
    
    // Check for validation errors or old data stored in session
    $errors = Session::get('validation_errors') ?? [];
    $formData = Session::get('form_data') ?? [];
    
    // Clear session storage for next time
    Session::remove('validation_errors');
    Session::remove('form_data');

    require_once __DIR__ . '/../views/users/form.php';
}

/**
 * Action: Process Create Form (POST)
 */
function handleCreatePost() {
    // 1. CSRF Token Validation
    $token = $_POST['csrf_token'] ?? '';
    if (!Csrf::validateToken($token)) {
        Session::set('error_msg', 'Security token validation failed. Please try again.');
        Session::set('form_data', $_POST);
        header("Location: UserController.php?action=create");
        exit;
    }

    // 2. Fetch and sanitize input data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    $status = trim($_POST['status'] ?? 'active');

    $errors = [];

    // 3. Validation Rules
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
        // Unique check
        $existing = User::findByEmail($email);
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

    // 4. Handle errors or create user
    if (!empty($errors)) {
        Session::set('validation_errors', $errors);
        Session::set('form_data', $_POST);
        header("Location: UserController.php?action=create");
        exit;
    }

    $createSuccess = User::create([
        'name' => $name,
        'email' => $email,
        'role' => $role,
        'password' => $password,
        'status' => $status
    ]);

    if ($createSuccess) {
        Session::set('success_msg', 'User successfully created.');
        header("Location: UserController.php?action=list");
    } else {
        Session::set('error_msg', 'An error occurred while creating the user.');
        Session::set('form_data', $_POST);
        header("Location: UserController.php?action=create");
    }
    exit;
}

/**
 * Action: Show Edit Form
 */
function handleEditGet() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $user = User::find($id);

    if (!$user) {
        Session::set('error_msg', 'User not found.');
        header("Location: UserController.php?action=list");
        exit;
    }

    $title = "Edit User";
    $submitUrl = "UserController.php?action=edit&id=" . $user['id'];

    // Retrieve session data if coming back from a validation error
    $errors = Session::get('validation_errors') ?? [];
    $formData = Session::get('form_data') ?? $user;

    // Clear session storage
    Session::remove('validation_errors');
    Session::remove('form_data');

    require_once __DIR__ . '/../views/users/form.php';
}

/**
 * Action: Process Edit Form (POST)
 */
function handleEditPost() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $user = User::find($id);

    if (!$user) {
        Session::set('error_msg', 'User not found.');
        header("Location: UserController.php?action=list");
        exit;
    }

    // 1. CSRF Token Validation
    $token = $_POST['csrf_token'] ?? '';
    if (!Csrf::validateToken($token)) {
        Session::set('error_msg', 'Security token validation failed. Please try again.');
        Session::set('form_data', $_POST);
        header("Location: UserController.php?action=edit&id=" . $id);
        exit;
    }

    // 2. Fetch and sanitize input
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    $status = trim($_POST['status'] ?? 'active');

    $errors = [];

    // 3. Validation Rules
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
        // Unique check (excluding the current user being edited)
        $existing = User::findByEmail($email);
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

    // 4. Self-protection: A user cannot disable their own currently logged-in account
    $currentLoggedIn = Auth::user();
    if ($currentLoggedIn && (int)$currentLoggedIn['id'] === $id && $status === 'disabled') {
        $errors['status'] = 'You cannot disable your own currently-logged-in account.';
    }

    // 5. Handle errors or update user
    if (!empty($errors)) {
        Session::set('validation_errors', $errors);
        
        // Populate form data, ensuring ID is kept
        $oldData = $_POST;
        $oldData['id'] = $id;
        Session::set('form_data', $oldData);
        
        header("Location: UserController.php?action=edit&id=" . $id);
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

    $updateSuccess = User::update($id, $updateData);

    if ($updateSuccess) {
        Session::set('success_msg', 'User successfully updated.');
        header("Location: UserController.php?action=list");
    } else {
        Session::set('error_msg', 'An error occurred while updating the user.');
        
        $oldData = $_POST;
        $oldData['id'] = $id;
        Session::set('form_data', $oldData);
        
        header("Location: UserController.php?action=edit&id=" . $id);
    }
    exit;
}

/**
 * Action: Toggle user status (active <-> disabled)
 */
function handleToggleStatus() {
    // CSRF token validation via GET or POST
    $token = $_GET['csrf_token'] ?? $_POST['csrf_token'] ?? '';
    if (!Csrf::validateToken($token)) {
        Session::set('error_msg', 'Security token validation failed. Please try again.');
        header("Location: UserController.php?action=list");
        exit;
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $user = User::find($id);

    if (!$user) {
        Session::set('error_msg', 'User not found.');
        header("Location: UserController.php?action=list");
        exit;
    }

    // Self-protection check
    $currentLoggedIn = Auth::user();
    if ($currentLoggedIn && (int)$currentLoggedIn['id'] === $id) {
        Session::set('error_msg', 'You cannot disable your own currently-logged-in account.');
        header("Location: UserController.php?action=list");
        exit;
    }

    $newStatus = ($user['status'] === 'active') ? 'disabled' : 'active';
    $success = User::setStatus($id, $newStatus);

    if ($success) {
        $statusWord = ($newStatus === 'active') ? 'enabled' : 'disabled';
        Session::set('success_msg', "User account successfully {$statusWord}.");
    } else {
        Session::set('error_msg', 'Failed to update user status.');
    }

    header("Location: UserController.php?action=list");
    exit;
}

/**
 * Action: Delete User (Soft Delete)
 */
function handleDelete() {
    // CSRF token validation via GET or POST
    $token = $_GET['csrf_token'] ?? $_POST['csrf_token'] ?? '';
    if (!Csrf::validateToken($token)) {
        Session::set('error_msg', 'Security token validation failed. Please try again.');
        header("Location: UserController.php?action=list");
        exit;
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $user = User::find($id);

    if (!$user) {
        Session::set('error_msg', 'User not found.');
        header("Location: UserController.php?action=list");
        exit;
    }

    // Self-protection check
    $currentLoggedIn = Auth::user();
    if ($currentLoggedIn && (int)$currentLoggedIn['id'] === $id) {
        Session::set('error_msg', 'You cannot delete your own currently-logged-in account.');
        header("Location: UserController.php?action=list");
        exit;
    }

    // Soft delete sets status to 'disabled'
    $success = User::delete($id);

    if ($success) {
        Session::set('success_msg', 'User account successfully deleted (soft-deleted / disabled).');
    } else {
        Session::set('error_msg', 'Failed to delete user account.');
    }

    header("Location: UserController.php?action=list");
    exit;
}
