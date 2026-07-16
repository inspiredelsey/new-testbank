<?php
/**
 * Question Model
 */

require_once __DIR__ . '/../../includes/Database.php';

class Question {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($filters = []) {
        $query = "
            SELECT q.*, c.name as category_name, u.name as creator_name 
            FROM questions q
            JOIN categories c ON q.category_id = c.id
            LEFT JOIN users u ON q.created_by = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['category_id'])) {
            $query .= " AND (q.category_id = ? OR q.category_id IN (SELECT id FROM categories WHERE parent_id = ?))";
            $params[] = $filters['category_id'];
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['type'])) {
            $query .= " AND q.type = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['difficulty'])) {
            $query .= " AND q.difficulty = ?";
            $params[] = $filters['difficulty'];
        }
        if (!empty($filters['status'])) {
            $query .= " AND q.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $query .= " AND q.question_text LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['tag'])) {
            $query .= " AND q.id IN (SELECT qt.question_id FROM question_tags qt JOIN tags t ON qt.tag_id = t.id WHERE t.name = ?)";
            $params[] = $filters['tag'];
        }

        $query .= " ORDER BY q.id DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getOptions($questionId) {
        $stmt = $this->db->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY order_index ASC, id ASC");
        $stmt->execute([$questionId]);
        return $stmt->fetchAll();
    }

    public function getTags($questionId) {
        $stmt = $this->db->prepare("
            SELECT t.* FROM tags t
            JOIN question_tags qt ON t.id = qt.tag_id
            WHERE qt.question_id = ?
        ");
        $stmt->execute([$questionId]);
        return $stmt->fetchAll();
    }

    public function create($data, $options = [], $tags = []) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO questions (category_id, type, question_text, difficulty, points, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['category_id'],
                $data['type'],
                $data['question_text'],
                $data['difficulty'],
                $data['points'] ?? 1.00,
                $data['status'] ?? 'published',
                $data['created_by'] ?? null
            ]);
            $questionId = $this->db->lastInsertId();

            // Save options
            $this->saveOptions($questionId, $data['type'], $options);

            // Save tags
            $this->saveTags($questionId, $tags);

            $this->db->commit();
            return $questionId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update($id, $data, $options = [], $tags = []) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE questions 
                SET category_id = ?, type = ?, question_text = ?, difficulty = ?, points = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['category_id'],
                $data['type'],
                $data['question_text'],
                $data['difficulty'],
                $data['points'] ?? 1.00,
                $data['status'] ?? 'published',
                $id
            ]);

            // Clear and resave options
            $stmtDel = $this->db->prepare("DELETE FROM question_options WHERE question_id = ?");
            $stmtDel->execute([$id]);
            $this->saveOptions($id, $data['type'], $options);

            // Resave tags
            $this->saveTags($id, $tags);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM questions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    private function saveOptions($questionId, $type, $options) {
        if ($type === 'essay') {
            return; // No options for essay questions
        }

        $stmt = $this->db->prepare("
            INSERT INTO question_options (question_id, option_text, is_correct, pair_key, order_index)
            VALUES (?, ?, ?, ?, ?)
        ");

        if ($type === 'true_false') {
            // True/False options: options parameter is either 'true' or 'false' indicating correct one
            $correctValue = strtolower(trim($options[0] ?? 'true'));
            $stmt->execute([$questionId, 'True', ($correctValue === 'true' ? 1 : 0), null, 0]);
            $stmt->execute([$questionId, 'False', ($correctValue === 'false' ? 1 : 0), null, 1]);
        } else {
            foreach ($options as $idx => $opt) {
                $optionText = $opt['option_text'] ?? '';
                if ($optionText === '' && !isset($opt['pair_key'])) continue;
                
                $isCorrect = !empty($opt['is_correct']) ? 1 : 0;
                $pairKey = !empty($opt['pair_key']) ? $opt['pair_key'] : null;
                $orderIdx = $opt['order_index'] ?? $idx;

                $stmt->execute([$questionId, $optionText, $isCorrect, $pairKey, $orderIdx]);
            }
        }
    }

    private function saveTags($questionId, $tags) {
        // Clear existing tags mapping
        $stmtDel = $this->db->prepare("DELETE FROM question_tags WHERE question_id = ?");
        $stmtDel->execute([$questionId]);

        if (empty($tags)) return;

        foreach ($tags as $tagName) {
            $tagName = trim(strtolower($tagName));
            if (empty($tagName)) continue;

            // Find or create tag
            $stmtTag = $this->db->prepare("SELECT id FROM tags WHERE name = ?");
            $stmtTag->execute([$tagName]);
            $tagId = $stmtTag->fetchColumn();

            if (!$tagId) {
                $stmtIns = $this->db->prepare("INSERT INTO tags (name) VALUES (?)");
                $stmtIns->execute([$tagName]);
                $tagId = $this->db->lastInsertId();
            }

            // Map question to tag
            $stmtMap = $this->db->prepare("INSERT IGNORE INTO question_tags (question_id, tag_id) VALUES (?, ?)");
            $stmtMap->execute([$questionId, $tagId]);
        }
    }

    /**
     * CSV Import Functionality
     */
    public function importCSV($filePath, $userId) {
        if (!file_exists($filePath)) {
            throw new Exception("CSV file not found.");
        }

        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file); // Read headers
        
        $importedCount = 0;
        
        // Find or create category cache
        $categoriesModel = new Category();
        $flatCats = $categoriesModel->getAll();
        $catCache = [];
        foreach ($flatCats as $c) {
            $catCache[strtolower($c['name'])] = $c['id'];
        }

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 5) continue; // Skip incomplete lines
            
            $catName = trim($row[0]);
            $type = trim($row[1]);
            $questionText = trim($row[2]);
            $difficulty = strtolower(trim($row[3]));
            $points = floatval($row[4]);
            $optionsRaw = $row[5] ?? '';
            $tagsRaw = $row[6] ?? '';

            if (empty($catName) || empty($type) || empty($questionText)) {
                continue; // Skip invalid
            }

            // Map difficulty
            if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
                $difficulty = 'medium';
            }

            // Map type
            if (!in_array($type, ['mcq_single', 'mcq_multi', 'true_false', 'fill_blank', 'matching', 'essay'])) {
                continue; // Skip invalid types
            }

            // Resolve category ID
            $catId = null;
            $catLower = strtolower($catName);
            if (isset($catCache[$catLower])) {
                $catId = $catCache[$catLower];
            } else {
                // Create category
                $catId = $categoriesModel->create([
                    'name' => $catName,
                    'parent_id' => null,
                    'description' => 'Imported via Question Bank CSV'
                ]);
                $catCache[$catLower] = $catId;
            }

            // Parse options
            $options = [];
            if ($type === 'true_false') {
                $options = [strtolower(trim($optionsRaw)) === 'true' ? 'true' : 'false'];
            } else if ($type === 'fill_blank') {
                $parts = explode(';', $optionsRaw);
                foreach ($parts as $part) {
                    if (trim($part) !== '') {
                        $options[] = [
                            'option_text' => trim($part),
                            'is_correct' => 1
                        ];
                    }
                }
            } else if ($type === 'matching') {
                // Matching options look like: ConceptA|pair_key=TermA;ConceptB|pair_key=TermB
                $parts = explode(';', $optionsRaw);
                foreach ($parts as $part) {
                    if (trim($part) === '') continue;
                    $subparts = explode('|pair_key=', $part);
                    $concept = trim($subparts[0]);
                    $term = trim($subparts[1] ?? '');
                    $options[] = [
                        'option_text' => $concept,
                        'pair_key' => $term,
                        'is_correct' => 1
                    ];
                }
            } else if ($type === 'essay') {
                $options = [];
            } else {
                // MCQ single/multi: OptionA|is_correct=1;OptionB|is_correct=0
                $parts = explode(';', $optionsRaw);
                foreach ($parts as $part) {
                    if (trim($part) === '') continue;
                    $subparts = explode('|is_correct=', $part);
                    $optText = trim($subparts[0]);
                    $isCorr = intval($subparts[1] ?? 0);
                    $options[] = [
                        'option_text' => $optText,
                        'is_correct' => $isCorr
                    ];
                }
            }

            // Parse tags
            $tags = [];
            if (!empty($tagsRaw)) {
                $tags = array_map('trim', explode(';', $tagsRaw));
            }

            $this->create([
                'category_id' => $catId,
                'type' => $type,
                'question_text' => $questionText,
                'difficulty' => $difficulty,
                'points' => $points,
                'status' => 'published',
                'created_by' => $userId
            ], $options, $tags);

            $importedCount++;
        }
        fclose($file);
        return $importedCount;
    }

    /**
     * CSV Export Functionality
     */
    public function exportCSV() {
        $questions = $this->getAll();
        
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['Category', 'Type', 'Question Text', 'Difficulty', 'Points', 'Options/Answers', 'Tags']);

        foreach ($questions as $q) {
            $options = $this->getOptions($q['id']);
            $tagsObj = $this->getTags($q['id']);
            
            $tags = implode(';', array_map(function($t) { return $t['name']; }, $tagsObj));
            
            $optionsStr = '';
            if ($q['type'] === 'true_false') {
                $correctVal = 'true';
                foreach ($options as $opt) {
                    if ($opt['is_correct']) {
                        $correctVal = strtolower($opt['option_text']);
                    }
                }
                $optionsStr = $correctVal;
            } else if ($q['type'] === 'fill_blank') {
                $optionsStr = implode(';', array_map(function($o) { return $o['option_text']; }, $options));
            } else if ($q['type'] === 'matching') {
                $optionsStr = implode(';', array_map(function($o) { 
                    return $o['option_text'] . '|pair_key=' . $o['pair_key']; 
                }, $options));
            } else if ($q['type'] === 'essay') {
                $optionsStr = '';
            } else {
                $optionsStr = implode(';', array_map(function($o) { 
                    return $o['option_text'] . '|is_correct=' . ($o['is_correct'] ? '1' : '0'); 
                }, $options));
            }

            fputcsv($output, [
                $q['category_name'],
                $q['type'],
                $q['question_text'],
                $q['difficulty'],
                $q['points'],
                $optionsStr,
                $tags
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }
}
