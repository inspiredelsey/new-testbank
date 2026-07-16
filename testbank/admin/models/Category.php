<?php
/**
 * Category Model
 */

require_once __DIR__ . '/../../includes/Database.php';

class Category {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $slug = $this->slugify($data['name']);
        
        // Ensure slug is unique
        $origSlug = $slug;
        $i = 1;
        while ($this->slugExists($slug)) {
            $slug = $origSlug . '-' . $i++;
        }

        $stmt = $this->db->prepare("
            INSERT INTO categories (parent_id, name, slug, description) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            !empty($data['parent_id']) ? $data['parent_id'] : null,
            $data['name'],
            $slug,
            $data['description'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        // Prevent setting a category's parent to itself
        if (!empty($data['parent_id']) && intval($data['parent_id']) === intval($id)) {
            throw new Exception("A category cannot be its own parent.");
        }

        $slug = $this->slugify($data['name']);
        // Ensure slug is unique except for this category
        $origSlug = $slug;
        $i = 1;
        while ($this->slugExists($slug, $id)) {
            $slug = $origSlug . '-' . $i++;
        }

        $stmt = $this->db->prepare("
            UPDATE categories 
            SET parent_id = ?, name = ?, slug = ?, description = ? 
            WHERE id = ?
        ");
        return $stmt->execute([
            !empty($data['parent_id']) ? $data['parent_id'] : null,
            $data['name'],
            $slug,
            $data['description'] ?? null,
            $id
        ]);
    }

    public function delete($id) {
        // Find if this category has children and set their parents to null or handle accordingly
        $stmt = $this->db->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id = ?");
        $stmt->execute([$id]);

        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Builds a tree hierarchy of categories
     */
    public function getTree($parentId = null) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE parent_id " . ($parentId === null ? "IS NULL" : "= ?") . " ORDER BY name ASC");
        if ($parentId === null) {
            $stmt->execute();
        } else {
            $stmt->execute([$parentId]);
        }
        $categories = $stmt->fetchAll();

        foreach ($categories as &$cat) {
            $cat['children'] = $this->getTree($cat['id']);
        }
        return $categories;
    }

    /**
     * Generates a flat array representing the hierarchical tree, with indentations for select dropdowns
     */
    public function getTreeFlat($parentId = null, $depth = 0, &$result = []) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE parent_id " . ($parentId === null ? "IS NULL" : "= ?") . " ORDER BY name ASC");
        if ($parentId === null) {
            $stmt->execute();
        } else {
            $stmt->execute([$parentId]);
        }
        $categories = $stmt->fetchAll();

        foreach ($categories as $cat) {
            $cat['depth'] = $depth;
            $cat['indented_name'] = str_repeat('— ', $depth) . $cat['name'];
            $result[] = $cat;
            $this->getTreeFlat($cat['id'], $depth + 1, $result);
        }
        return $result;
    }

    private function slugify($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }

    private function slugExists($slug, $excludeId = null) {
        $query = "SELECT COUNT(*) FROM categories WHERE slug = ?";
        $params = [$slug];
        if ($excludeId !== null) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
}
