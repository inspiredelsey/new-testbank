<?php
/**
 * User Model Class for Test Bank LMS
 * Handles CRUD operations for the 'users' table using PDO.
 */

require_once __DIR__ . '/../../includes/Database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieve all users, optionally filtering by role and/or status.
     * 
     * @param string|null $role
     * @param string|null $status
     * @return array
     */
    public function all($role = null, $status = null) {
        $query = "SELECT id, name, email, role, status FROM users WHERE 1=1";
        $params = [];

        if (!empty($role)) {
            $query .= " AND role = :role";
            $params['role'] = $role;
        }

        if (!empty($status)) {
            $query .= " AND status = :status";
            $params['status'] = $status;
        }

        $query .= " ORDER BY id DESC";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("User::all error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a user by their ID.
     * 
     * @param int $id
     * @return array|null
     */
    public function find($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, name, email, password_hash, role, status, email_notifications, timezone FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch();
            return $user ? $user : null;
        } catch (PDOException $e) {
            error_log("User::find error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a user by their email.
     * 
     * @param string $email
     * @return array|null
     */
    public function findByEmail($email) {
        try {
            $stmt = $this->db->prepare("SELECT id, name, email, password_hash, role, status FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            return $user ? $user : null;
        } catch (PDOException $e) {
            error_log("User::findByEmail error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new user. Plaintext password is automatically hashed.
     * 
     * @param array $data Contains keys: name, email, password, role, status (optional)
     * @return bool
     */
    public function create($data) {
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $status = $data['status'] ?? 'active';

        try {
            $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (:name, :email, :password_hash, :role, :status)");
            return $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password_hash' => $passwordHash,
                'role' => $data['role'],
                'status' => $status
            ]);
        } catch (PDOException $e) {
            error_log("User::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing user.
     * Plaintext password is only hashed and updated if a non-empty password is provided.
     * 
     * @param int $id
     * @param array $data Contains keys: name, email, role, status (optional), password (optional)
     * @return bool
     */
    public function update($id, $data) {
        $query = "UPDATE users SET name = :name, email = :email, role = :role";
        $params = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role']
        ];

        if (isset($data['status'])) {
            $query .= ", status = :status";
            $params['status'] = $data['status'];
        }

        if (!empty($data['password'])) {
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            $query .= ", password_hash = :password_hash";
            $params['password_hash'] = $passwordHash;
        }

        $query .= " WHERE id = :id";

        try {
            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("User::update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update only the status of a user (e.g. active/disabled).
     * 
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function setStatus($id, $status) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET status = :status WHERE id = :id");
            return $stmt->execute([
                'id' => $id,
                'status' => $status
            ]);
        } catch (PDOException $e) {
            error_log("User::setStatus error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a user (soft delete).
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        return $this->setStatus($id, 'disabled');
    }

    /**
     * Self-service profile update — name and email ONLY. Deliberately
     * separate from update() above, which also accepts role/status and
     * is meant for admin use. A user editing their own profile must never
     * be able to change their own role through this path.
     */
    public function updateProfile($id, $name, $email) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
            return $stmt->execute(['id' => $id, 'name' => $name, 'email' => $email]);
        } catch (PDOException $e) {
            error_log("User::updateProfile error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Self-service password change — password ONLY. The controller must
     * verify the person's current password before calling this; this
     * method itself does not re-check anything, it just writes the hash.
     */
    public function updatePassword($id, $newPassword) {
        try {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            return $stmt->execute(['id' => $id, 'hash' => $hash]);
        } catch (PDOException $e) {
            error_log("User::updatePassword error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Self-service notification/timezone preferences.
     */
    public function updatePreferences($id, $emailNotifications, $timezone) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET email_notifications = :en, timezone = :tz WHERE id = :id");
            return $stmt->execute([
                'id' => $id,
                'en' => $emailNotifications ? 1 : 0,
                'tz' => $timezone,
            ]);
        } catch (PDOException $e) {
            error_log("User::updatePreferences error: " . $e->getMessage());
            return false;
        }
    }
}
