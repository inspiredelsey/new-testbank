<?php
/**
 * Account Controller
 * Self-service Profile Information, Account Settings (password), and
 * Preferences (notifications/timezone) for any logged-in user.
 */

require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../models/User.php';

class AccountController {
    private $userModel;

    public function __construct() {
        Auth::requireLogin();
        $this->userModel = new User();
    }

    public function handleRequest($action = 'profile') {
        switch ($action) {
            case 'settings':
                $this->handleSettings();
                break;
            case 'preferences':
                $this->handlePreferences();
                break;
            case 'profile':
            default:
                $this->handleProfile();
                break;
        }
    }

    private function handleProfile() {
        $currentUser = Auth::getUser();
        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
                $error = 'Security check failed, please try again.';
            } else {
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');

                if (empty($name) || empty($email)) {
                    $error = 'Name and email are both required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address.';
                } else {
                    $existing = $this->userModel->findByEmail($email);
                    if ($existing && intval($existing['id']) !== intval($currentUser['id'])) {
                        $error = 'That email address is already in use by another account.';
                    } else {
                        if ($this->userModel->updateProfile($currentUser['id'], $name, $email)) {
                            Session::set('user_name', $name);
                            Session::set('user_email', $email);
                            $currentUser = Auth::getUser();
                            $success = 'Your profile has been updated.';
                        } else {
                            $error = 'Something went wrong updating your profile. Please try again.';
                        }
                    }
                }
            }
        }

        $user = $currentUser;
        include __DIR__ . '/../views/account/profile.php';
    }

    private function handleSettings() {
        $currentUser = Auth::getUser();
        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
                $error = 'Security check failed, please try again.';
            } else {
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                $userRow = $this->userModel->find($currentUser['id']);

                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $error = 'Please fill in all fields.';
                } elseif (!$userRow || !password_verify($currentPassword, $userRow['password_hash'])) {
                    $error = 'Your current password is incorrect.';
                } elseif (strlen($newPassword) < 8) {
                    $error = 'New password must be at least 8 characters.';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'New password and confirmation do not match.';
                } else {
                    if ($this->userModel->updatePassword($currentUser['id'], $newPassword)) {
                        $success = 'Your password has been changed.';
                    } else {
                        $error = 'Something went wrong changing your password. Please try again.';
                    }
                }
            }
        }

        $user = $currentUser;
        include __DIR__ . '/../views/account/settings.php';
    }

    private function handlePreferences() {
        $currentUser = Auth::getUser();
        $error = null;
        $success = null;

        // A short, reasonable list rather than every IANA timezone —
        // covers common regions without an overwhelming dropdown.
        $timezoneOptions = [
            'UTC' => 'UTC',
            'America/New_York' => 'Eastern Time (US)',
            'America/Chicago' => 'Central Time (US)',
            'America/Denver' => 'Mountain Time (US)',
            'America/Los_Angeles' => 'Pacific Time (US)',
            'Europe/London' => 'London',
            'Europe/Paris' => 'Paris/Berlin',
            'Africa/Lagos' => 'West Africa (Lagos)',
            'Africa/Johannesburg' => 'South Africa',
            'Asia/Dubai' => 'Dubai',
            'Asia/Kolkata' => 'India',
            'Asia/Singapore' => 'Singapore',
            'Asia/Tokyo' => 'Tokyo',
            'Australia/Sydney' => 'Sydney',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($_POST['csrf_token'] ?? '')) {
                $error = 'Security check failed, please try again.';
            } else {
                $emailNotifications = isset($_POST['email_notifications']);
                $timezone = $_POST['timezone'] ?? 'UTC';

                if (!array_key_exists($timezone, $timezoneOptions)) {
                    $error = 'Please select a valid timezone.';
                } else {
                    if ($this->userModel->updatePreferences($currentUser['id'], $emailNotifications, $timezone)) {
                        $success = 'Your preferences have been saved.';
                    } else {
                        $error = 'Something went wrong saving your preferences. Please try again.';
                    }
                }
            }
        }

        $user = $this->userModel->find($currentUser['id']);
        include __DIR__ . '/../views/account/preferences.php';
    }
}
