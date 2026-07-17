<?php
/**
 * Category Model
 * Wraps CRUD for the 'categories' table via Database::getInstance() and PDO prepared statements.
 */

require_once __DIR__ . '/../../includes/Database.php';

class Category {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieve all categories (flat list).
     * 
     * @return array
     */
    public function all() {
        $stmt = $this->db->prepare("SELECT * FROM categories ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Retrieve all categories structured as a nested tree.
     * 
     * @return array
     */
    public function tree() {
        $categories = $this->all();
        $map = [];
        $tree = [];

        foreach ($categories as $cat) {
            $cat['children'] = [];
            $map[$cat['id']] = $cat;
        }

        foreach ($map as $id => &$cat) {
            $parentId = $cat['parent_id'];
            if ($parentId !== null && isset($map[$parentId])) {
                $map[$parentId]['children'][] = &$cat;
            } else {
                $tree[] = &$cat;
            }
        }

        return $tree;
    }

    /**
     * Find a category by its ID.
     * 
     * @param int $id
     * @return array|null
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Retrieve direct children of a given parent ID, or top-level if null.
     * 
     * @param int|null $parentId
     * @return array
     */
    public function children($parentId) {
        if ($parentId === null) {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY name ASC");
            $stmt->execute([$parentId]);
        }
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Recursively find all descendant category IDs for a given parent category ID.
     * 
     * @param int $id
     * @return array
     */
    public function getDescendantIds($id) {
        $ids = [];
        $children = $this->children($id);
        foreach ($children as $child) {
            $ids[] = intval($child['id']);
            $ids = array_merge($ids, $this->getDescendantIds($child['id']));
        }
        return $ids;
    }

    /**
     * Create a new category with automated unique slug generation.
     * 
     * @param array $data
     * @return int Inserted ID
     */
    public function create($data) {
        if (empty($data['name'])) {
            throw new Exception("Category name is required.");
        }
        if (strlen($data['name']) > 150) {
            throw new Exception("Category name must not exceed 150 characters.");
        }

        $parentId = !empty($data['parent_id']) ? intval($data['parent_id']) : null;
        if ($parentId !== null) {
            $parent = $this->find($parentId);
            if (!$parent) {
                throw new Exception("Selected parent category does not exist.");
            }
        }

        $slug = $this->getUniqueSlug($data['name']);
        $description = !empty($data['description']) ? $data['description'] : null;

        $stmt = $this->db->prepare("
            INSERT INTO categories (parent_id, name, slug, description)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$parentId, $data['name'], $slug, $description]);
        return $this->db->lastInsertId();
    }

    /**
     * Update an existing category with circular reference protection and slug uniqueness.
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $category = $this->find($id);
        if (!$category) {
            throw new Exception("Category not found.");
        }

        if (empty($data['name'])) {
            throw new Exception("Category name is required.");
        }
        if (strlen($data['name']) > 150) {
            throw new Exception("Category name must not exceed 150 characters.");
        }

        $parentId = !empty($data['parent_id']) ? intval($data['parent_id']) : null;
        if ($parentId !== null) {
            if ($parentId === intval($id)) {
                throw new Exception("A category cannot be set as its own parent.");
            }
            $descendantIds = $this->getDescendantIds($id);
            if (in_array($parentId, $descendantIds)) {
                throw new Exception("Circular reference detected: Parent category cannot be a descendant of this category.");
            }
            $parent = $this->find($parentId);
            if (!$parent) {
                throw new Exception("Selected parent category does not exist.");
            }
        }

        $slug = $this->getUniqueSlug($data['name'], $id);
        $description = !empty($data['description']) ? $data['description'] : null;

        $stmt = $this->db->prepare("
            UPDATE categories
            SET parent_id = ?, name = ?, slug = ?, description = ?
            WHERE id = ?
        ");
        return $stmt->execute([$parentId, $data['name'], $slug, $description, $id]);
    }

    /**
     * Safely delete a category if it has no children or associated courses.
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        // 1. Check if the category has child categories
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Cannot delete category because it has subcategories.");
        }

        // 2. Check if the category is referenced by any courses row
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM courses WHERE category_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Cannot delete category because it is referenced by one or more courses.");
            }
        } catch (PDOException $e) {
            // Safe fallback if column category_id doesn't exist in courses yet
            error_log("Courses table check skipped or failed: " . $e->getMessage());
        }

        // 3. Do the deletion
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Builds a tree hierarchy of categories (Legacy support)
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
     * Generates a flat array representing the hierarchical tree, with indentations for select dropdowns (Legacy support)
     */
    public function getTreeFlat($parentId = null, $depth = 0, &$result = []) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE parent_id " . ($parentId === null ? "IS NULL" : "= ?") . " ORDER BY name ASC");
        if ($parentId === null) {
            $stmt->execute();
        } else {
            $stmt->execute([$parentId]);
        }
        $categories = $stmt->fetchAll() ?: [];

        foreach ($categories as $cat) {
            $cat['depth'] = $depth;
            $cat['indented_name'] = str_repeat('— ', $depth) . $cat['name'];
            $result[] = $cat;
            $this->getTreeFlat($cat['id'], $depth + 1, $result);
        }
        return $result;
    }

    /**
     * Helper to slugify category name
     */
    private function slugify($name) {
        $slug = strtolower($name);
        $slug = str_replace(' ', '-', $slug);
        // Strip non-alphanumeric characters except hyphens
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        // Replace multiple hyphens with single hyphen
        $slug = preg_replace('/-+/', '-', $slug);
        // Trim hyphens from ends
        $slug = trim($slug, '-');
        if (empty($slug)) {
            $slug = 'category';
        }
        return $slug;
    }

    /**
     * Helper to guarantee unique slug creation
     */
    private function getUniqueSlug($name, $excludeId = null) {
        $baseSlug = $this->slugify($name);
        $slug = $baseSlug;
        $i = 1;
        
        while (true) {
            $query = "SELECT COUNT(*) FROM categories WHERE slug = ?";
            $params = [$slug];
            if ($excludeId !== null) {
                $query .= " AND id != ?";
                $params[] = $excludeId;
            }
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            if ($stmt->fetchColumn() == 0) {
                break;
            }
            $i++;
            $slug = $baseSlug . '-' . $i;
        }
        return $slug;
    }
}
