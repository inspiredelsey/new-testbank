<?php
/**
 * Group Controller for LMS Bank Admin Panel
 * Handles all Group management views and actions.
 */

require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Csrf.php';
require_once __DIR__ . '/../models/Group.php';
require_once __DIR__ . '/../models/User.php';

Session::start();

// Strict Access Control: Admin and Instructor only
// Future refinement: restrict instructors to only manage groups tied to their own courses
Auth::requireRole(['admin', 'instructor']);

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

    case 'delete':
        handleDelete();
        break;

    case 'members':
        handleMembersGet();
        break;

    case 'add_member':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handleAddMemberPost();
        } else {
            header("Location: GroupController.php?action=list");
            exit;
        }
        break;

    case 'remove_member':
        handleRemoveMember();
        break;

    default:
        header("Location: GroupController.php?action=list");
        exit;
}

/**
 * Action: List Groups
 */
function handleList() {
    $groups = Group::all();
    require_once __DIR__ . '/../views/groups/list.php';
}

/**
 * Action: Show Create Form
 */
function handleCreateGet() {
    $title = "Add Group";
    $submitUrl = "GroupController.php?action=create";
    
    // Check for validation errors or old data stored in session
    $errors = Session::get('validation_errors') ?? [];
    $formData = Session::get('form_data') ?? [];
    
    // Clear session storage for next time
    Session::remove('validation_errors');
    Session::remove('form_data');

    require_once __DIR__ . '/../views/groups/form.php';
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
        header("Location: GroupController.php?action=create");
        exit;
    }

    // 2. Fetch and sanitize input
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $errors = [];

    // 3. Validation Rules
    if (empty($name)) {
        $errors['name'] = 'Group name is required.';
    } elseif (strlen($name) > 150) {
        $errors['name'] = 'Group name cannot exceed 150 characters.';
    }

    // 4. Handle errors or create group
    if (!empty($errors)) {
        Session::set('validation_errors', $errors);
        Session::set('form_data', $_POST);
        header("Location: GroupController.php?action=create");
        exit;
    }

    $createSuccess = Group::create([
        'name' => $name,
        'description' => $description
    ]);

    if ($createSuccess) {
        Session::set('success_msg', 'Group successfully created.');
        header("Location: GroupController.php?action=list");
    } else {
        Session::set('error_msg', 'An error occurred while creating the group.');
        Session::set('form_data', $_POST);
        header("Location: GroupController.php?action=create");
    }
    exit;
}

/**
 * Action: Show Edit Form
 */
function handleEditGet() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $group = Group::find($id);

    if (!$group) {
        Session::set('error_msg', 'Group not found.');
        header("Location: GroupController.php?action=list");
        exit;
    }

    $title = "Edit Group";
    $submitUrl = "GroupController.php?action=edit&id=" . $group['id'];

    // Retrieve session data if coming back from a validation error
    $errors = Session::get('validation_errors') ?? [];
    $formData = Session::get('form_data') ?? $group;

    // Clear session storage
    Session::remove('validation_errors');
    Session::remove('form_data');

    require_once __DIR__ . '/../views/groups/form.php';
}

/**
 * Action: Process Edit Form (POST)
 */
function handleEditPost() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $group = Group::find($id);

    if (!$group) {
        Session::set('error_msg', 'Group not found.');
        header("Location: GroupController.php?action=list");
        exit;
    }

    // 1. CSRF Token Validation
    $token = $_POST['csrf_token'] ?? '';
    if (!Csrf::validateToken($token)) {
        Session::set('error_msg', 'Security token validation failed. Please try again.');
        Session::set('form_data', $_POST);
        header("Location: GroupController.php?action=edit&id=" . $id);
        exit;
    }

    // 2. Fetch and sanitize input
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $errors = [];

    // 3. Validation Rules
    if (empty($name)) {
        $errors['name'] = 'Group name is required.';
    } elseif (strlen($name) > 150) {
        $errors['name'] = 'Group name cannot exceed 150 characters.';
    }

    // 4. Handle errors or update group
    if (!empty($errors)) {
        Session::set('validation_errors', $errors);
        
        $oldData = $_POST;
        $oldData['id'] = $id;
        Session::set('form_data', $oldData);
        
        header("Location: GroupController.php?action=edit&id=" . $id);
        exit;
    }

    $updateSuccess = Group::update($id, [
        'name' => $name,
        'description' => $description
    ]);

    if ($updateSuccess) {
        Session::set('success_msg', 'Group successfully updated.');
        header("Location: GroupController.php?action=list");
    } else {
        Session::set('error_msg', 'An error occurred while updating the group.');
        
        $oldData = $_POST;
        $oldData['id'] = $id;
        Session::set('form_data', $oldData);
        
        header("Location: GroupController.php?action=edit&id=" . $id);
    }
    exit;
}

/**
 * Action: Delete Group
 */
function handleDelete() {
    // CSRF token validation
    $token = $_GET['csrf_token'] ?? $_POST['csrf_token'] ?? '';
    if (!Csrf::validateToken($token)) {
        Session::set('error_msg', 'Security token validation failed. Please try again.');
        header("Location: GroupController.php?action=list");
        exit;
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $group = Group::find($id);

    if (!$group) {
        Session::set('error_msg', 'Group not found.');
        header("Location: GroupController.php?action=list");
        exit;
    }

    try {
        $success = Group::delete($id);
        if ($success) {
            Session::set('success_msg', 'Group successfully deleted.');
        } else {
            Session::set('error_msg', 'An error occurred while deleting the group.');
        }
    } catch (Exception $e) {
        // Capture the validation warning exception from the model
        Session::set('error_msg', $e->getMessage());
    }

    header("Location: GroupController.php?action=list");
    exit;
}

/**
 * Action: Show Manage Members View
 */
function handleMembersGet() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $group = Group::find($id);

    if (!$group) {
        Session::set('error_msg', 'Group not found.');
        header("Location: GroupController.php?action=list");
        exit;
    }

    $members = Group::members($id);

    // Fetch all active users who are NOT currently in this group
    $db = Database::getInstance();
    $availableUsers = [];
    try {
        $stmt = $db->prepare("
            SELECT id, name, email, role 
            FROM users 
            WHERE status = 'active' 
              AND id NOT IN (SELECT user_id FROM group_members WHERE group_id = :group_id)
            ORDER BY name ASC
        ");
        $stmt->execute(['group_id' => $id]);
        $availableUsers = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("GroupController fetching available users error: " . $e->getMessage());
    }

    // Retrieve and clear flash messages for members action
    $successMsg = Session::get('success_msg');
    $errorMsg = Session::get('error_msg');
    Session::remove('success_msg');
    Session::remove('error_msg');

    require_once __DIR__ . '/../views/groups/members.php';
}

/**
 * Action: Add Member (POST)
 */
function handleAddMemberPost() {
    $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $group = Group::find($groupId);

    if (!$group) {
        Session::set('error_msg', 'Group not found.');
        header("Location: GroupController.php?action=list");
        exit;
    }

    // 1. CSRF Token Validation
    $token = $_POST['csrf_token'] ?? '';
    if (!Csrf::validateToken($token)) {
        Session::set('error_msg', 'Security token validation failed. Please try again.');
        header("Location: GroupController.php?action=members&id=" . $groupId);
        exit;
    }

    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $user = User::find($userId);

    if (!$user) {
        Session::set('error_msg', 'The selected user does not exist.');
        header("Location: GroupController.php?action=members&id=" . $groupId);
        exit;
    }

    if ($user['status'] !== 'active') {
        Session::set('error_msg', 'Cannot add a disabled user to a group.');
        header("Location: GroupController.php?action=members&id=" . $groupId);
        exit;
    }

    // 2. Prevent Duplicate Member Additions
    if (Group::isMember($groupId, $userId)) {
        Session::set('error_msg', 'User is already a member of this group.');
        header("Location: GroupController.php?action=members&id=" . $groupId);
        exit;
    }

    $success = Group::addMember($groupId, $userId);

    if ($success) {
        Session::set('success_msg', "Successfully added '" . $user['name'] . "' to the group.");
    } else {
        Session::set('error_msg', 'An error occurred while adding the member.');
    }

    header("Location: GroupController.php?action=members&id=" . $groupId);
    exit;
}

/**
 * Action: Remove Member (GET/POST)
 */
function handleRemoveMember() {
    $token = $_GET['csrf_token'] ?? $_POST['csrf_token'] ?? '';
    $groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : (isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0);
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0);

    $group = Group::find($groupId);
    if (!$group) {
        Session::set('error_msg', 'Group not found.');
        header("Location: GroupController.php?action=list");
        exit;
    }

    // 1. CSRF Validation
    if (!Csrf::validateToken($token)) {
        Session::set('error_msg', 'Security token validation failed. Please try again.');
        header("Location: GroupController.php?action=members&id=" . $groupId);
        exit;
    }

    $user = User::find($userId);
    if (!$user) {
        Session::set('error_msg', 'User not found.');
        header("Location: GroupController.php?action=members&id=" . $groupId);
        exit;
    }

    // 2. Perform Removal
    $success = Group::removeMember($groupId, $userId);

    if ($success) {
        Session::set('success_msg', "Successfully removed '" . $user['name'] . "' from the group.");
    } else {
        Session::set('error_msg', 'An error occurred while removing the member.');
    }

    header("Location: GroupController.php?action=members&id=" . $groupId);
    exit;
}
