<?php
/**
 * Group Model Class for Test Bank LMS
 * Handles CRUD and member operations for the 'groups' and 'group_members' tables using PDO.
 */

require_once __DIR__ . '/../../includes/Database.php';

class Group {
    /**
     * Retrieve all groups with member counts.
     * 
     * @return array
     */
    public static function all() {
        $db = Database::getInstance()->getConnection();
        $query = "SELECT g.id, g.name, g.description, COUNT(gm.user_id) AS member_count 
                  FROM `groups` g 
                  LEFT JOIN group_members gm ON g.id = gm.group_id 
                  GROUP BY g.id, g.name, g.description
                  ORDER BY g.name ASC";
        try {
            $stmt = $db->query($query);
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Group::all error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a group by its ID.
     * 
     * @param int $id
     * @return array|null
     */
    public static function find($id) {
        $db = Database::getInstance()->getConnection();
        try {
            $stmt = $db->prepare("SELECT id, name, description FROM `groups` WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $group = $stmt->fetch();
            return $group ? $group : null;
        } catch (PDOException $e) {
            error_log("Group::find error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new group.
     * 
     * @param array $data Contains keys: name, description (optional)
     * @return bool
     */
    public static function create($data) {
        $db = Database::getInstance()->getConnection();
        $description = $data['description'] ?? null;
        try {
            $stmt = $db->prepare("INSERT INTO `groups` (name, description) VALUES (:name, :description)");
            return $stmt->execute([
                'name' => $data['name'],
                'description' => $description
            ]);
        } catch (PDOException $e) {
            error_log("Group::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing group.
     * 
     * @param int $id
     * @param array $data Contains keys: name, description (optional)
     * @return bool
     */
    public static function update($id, $data) {
        $db = Database::getInstance()->getConnection();
        $description = $data['description'] ?? null;
        try {
            $stmt = $db->prepare("UPDATE `groups` SET name = :name, description = :description WHERE id = :id");
            return $stmt->execute([
                'id' => $id,
                'name' => $data['name'],
                'description' => $description
            ]);
        } catch (PDOException $e) {
            error_log("Group::update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a group.
     * Checks for members and course_enrollments associations before deleting.
     * 
     * @param int $id
     * @throws Exception if group cannot be deleted due to existing members or enrollments
     * @return bool
     */
    public static function delete($id) {
        $db = Database::getInstance()->getConnection();

        // 1. Check if group has members
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = :group_id");
            $stmt->execute(['group_id' => $id]);
            $memberCount = (int)$stmt->fetchColumn();
            if ($memberCount > 0) {
                throw new Exception("Cannot delete group because it currently has " . $memberCount . " member(s). Please remove all members first.");
            }
        } catch (PDOException $e) {
            error_log("Group::delete check members error: " . $e->getMessage());
            throw new Exception("Database error while checking group members.");
        }

        // 2. Check if group is referenced by course_enrollments
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM course_enrollments WHERE group_id = :group_id");
            $stmt->execute(['group_id' => $id]);
            $enrollmentCount = (int)$stmt->fetchColumn();
            if ($enrollmentCount > 0) {
                throw new Exception("Cannot delete group because it is linked to " . $enrollmentCount . " course enrollment(s). Please delete or update those enrollments first.");
            }
        } catch (PDOException $e) {
            error_log("Group::delete check enrollments error: " . $e->getMessage());
            throw new Exception("Database error while checking course enrollments.");
        }

        // 3. Delete group
        try {
            $stmt = $db->prepare("DELETE FROM `groups` WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Group::delete execution error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve all members of a group.
     * 
     * @param int $groupId
     * @return array
     */
    public static function members($groupId) {
        $db = Database::getInstance()->getConnection();
        try {
            $stmt = $db->prepare("
                SELECT u.id, u.name, u.email, u.role, u.status 
                FROM users u 
                INNER JOIN group_members gm ON u.id = gm.user_id 
                WHERE gm.group_id = :group_id 
                ORDER BY u.name ASC
            ");
            $stmt->execute(['group_id' => $groupId]);
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Group::members error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if a user is a member of a group.
     * 
     * @param int $groupId
     * @param int $userId
     * @return bool
     */
    public static function isMember($groupId, $userId) {
        $db = Database::getInstance()->getConnection();
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = :group_id AND user_id = :user_id");
            $stmt->execute([
                'group_id' => $groupId,
                'user_id' => $userId
            ]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Group::isMember error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a member to a group.
     * 
     * @param int $groupId
     * @param int $userId
     * @return bool
     */
    public static function addMember($groupId, $userId) {
        // Prevent duplicate membership
        if (self::isMember($groupId, $userId)) {
            return true;
        }

        $db = Database::getInstance()->getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO group_members (group_id, user_id) VALUES (:group_id, :user_id)");
            return $stmt->execute([
                'group_id' => $groupId,
                'user_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Group::addMember error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a member from a group.
     * 
     * @param int $groupId
     * @param int $userId
     * @return bool
     */
    public static function removeMember($groupId, $userId) {
        $db = Database::getInstance()->getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM group_members WHERE group_id = :group_id AND user_id = :user_id");
            return $stmt->execute([
                'group_id' => $groupId,
                'user_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Group::removeMember error: " . $e->getMessage());
            return false;
        }
    }
}
