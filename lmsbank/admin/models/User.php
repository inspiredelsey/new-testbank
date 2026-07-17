<?php
/**
 * User Model Class for LMS Bank
 * Handles CRUD operations for the 'users' table using PDO.
 */

require_once __DIR__ . '/../../includes/Database.php';

class User {
    /**
     * Retrieve all users, optionally filtering by role and/or status.
     * 
     * @param string|null $role
     * @param string|null $status
     * @return array
     */
    public static function all($role = null, $status = null) {
        $db = Database::getInstance();
        $query = "SELECT id, name, email, role, status, created_at FROM users WHERE 1=1";
        $params = [];

        if (!empty($role)) {
            $query .= " AND role = :role";
            $params['role'] = $role;
        }

        if (!empty($status)) {
            $query .= " AND status = :status";
            $params['status'] = $status;
        }

        $query .= " ORDER BY created_at DESC";

        try {
            $stmt = $db->prepare($query);
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
    public static function find($id) {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT id, name, email, password_hash, role, status, created_at FROM users WHERE id = :id LIMIT 1");
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
    public static function findByEmail($email) {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT id, name, email, password_hash, role, status, created_at FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            return $user ? $user : null;
        } catch (PDOException $e) {
            error_log("User::findByEmail error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Filter users by role (helper/alias for all method).
     * 
     * @param string|null $role
     * @return array
     */
    public static function listByRole($role) {
        return self::all($role);
    }

    /**
     * Create a new user. Plaintext password is automatically hashed.
     * 
     * @param array $data Contains keys: name, email, password, role, status (optional)
     * @return bool
     */
    public static function create($data) {
        $db = Database::getInstance();
        
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        $status = $data['status'] ?? 'active';

        try {
            $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (:name, :email, :password_hash, :role, :status)");
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
    public static function update($id, $data) {
        $db = Database::getInstance();
        
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
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
            $query .= ", password_hash = :password_hash";
            $params['password_hash'] = $passwordHash;
        }

        $query .= " WHERE id = :id";

        try {
            $stmt = $db->prepare($query);
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
    public static function setStatus($id, $status) {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
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
     * Delete a user.
     * 
     * DECISION: Soft-delete is selected and recommended over hard-deleting
     * because of potential relational integrity issues with courses and enrollments.
     * Soft-deleting via setting the status to 'disabled' preserves audit logs and historical associations.
     * 
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        // We implement soft-delete by setting the status to 'disabled'
        return self::setStatus($id, 'disabled');
    }
}
