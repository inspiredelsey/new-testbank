<?php
/**
 * Question Model - Test Bank LMS
 * Handles CRUD and schema validation for NGN and standard questions.
 */

require_once __DIR__ . '/../../includes/Database.php';

class Question {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureSchemaUpgraded();
    }

    /**
     * Upgrades/ensures the questions table matches the required NGN specifications
     */
    private function ensureSchemaUpgraded() {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

        // Try adding required NGN columns to the questions table
        $columnsToAdd = [
            'case_id' => 'INT NULL',
            'case_order' => 'INT NULL',
            'question_data' => 'TEXT NULL',
            'scoring_method' => "VARCHAR(50) DEFAULT 'all_or_nothing'"
        ];

        foreach ($columnsToAdd as $col => $definition) {
            try {
                $this->db->exec("ALTER TABLE questions ADD COLUMN {$col} {$definition}");
            } catch (PDOException $e) {
                // Ignore column already exists errors
            }
        }
    }

    /**
     * Retrieve all questions with filters
     */
    public function getAll($filters = []) {
        $sql = "SELECT q.*, c.name as category_name, u.name as creator_name, cs.title as case_title
                FROM questions q
                LEFT JOIN categories c ON q.category_id = c.id
                LEFT JOIN users u ON q.created_by = u.id
                LEFT JOIN cases cs ON q.case_id = cs.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= " AND q.category_id = ?";
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['type'])) {
            $sql .= " AND q.type = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['difficulty'])) {
            $sql .= " AND q.difficulty = ?";
            $params[] = $filters['difficulty'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND q.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND q.question_text LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY q.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        // Automatically decode question_data
        foreach ($results as &$row) {
            $row['question_data'] = !empty($row['question_data']) ? json_decode($row['question_data'], true) : [];
        }

        return $results;
    }

    /**
     * Retrieve questions attached to a case, ordered by case_order
     */
    public function forCase($caseId) {
        $stmt = $this->db->prepare("SELECT q.*, c.name as category_name 
                                    FROM questions q
                                    LEFT JOIN categories c ON q.category_id = c.id
                                    WHERE q.case_id = ? 
                                    ORDER BY q.case_order ASC, q.id ASC");
        $stmt->execute([$caseId]);
        $results = $stmt->fetchAll();

        foreach ($results as &$row) {
            $row['question_data'] = !empty($row['question_data']) ? json_decode($row['question_data'], true) : [];
        }

        return $results;
    }

    /**
     * Find a single question by ID
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT q.*, c.name as category_name, cs.title as case_title 
                                    FROM questions q
                                    LEFT JOIN categories c ON q.category_id = c.id
                                    LEFT JOIN cases cs ON q.case_id = cs.id
                                    WHERE q.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row) {
            $row['question_data'] = !empty($row['question_data']) ? json_decode($row['question_data'], true) : [];
        }

        return $row;
    }

    /**
     * Validate the JSON question data shape before saving
     */
    public function validateQuestionData($type, $data) {
        if (!is_array($data)) {
            throw new Exception("Question data must be a structured array.");
        }

        if (in_array($type, ['mcq_single', 'mcq_multi_sata', 'true_false'])) {
            if (!isset($data['options']) || !is_array($data['options'])) {
                throw new Exception("Missing options array.");
            }
            if (empty($data['options'])) {
                throw new Exception("At least one option is required.");
            }

            $correctCount = 0;
            foreach ($data['options'] as $idx => $opt) {
                if (!isset($opt['id']) || !isset($opt['text'])) {
                    throw new Exception("Option row #" . ($idx + 1) . " is missing 'id' or 'text'.");
                }
                if (!empty($opt['is_correct'])) {
                    $correctCount++;
                }
            }

            if (($type === 'mcq_single' || $type === 'true_false') && $correctCount !== 1) {
                throw new Exception("Exactly one option must be marked as correct.");
            }
            if ($type === 'mcq_multi_sata' && $correctCount < 1) {
                throw new Exception("At least one option must be marked as correct.");
            }
        } elseif ($type === 'matching') {
            if (!isset($data['left']) || !is_array($data['left']) || empty($data['left'])) {
                throw new Exception("Left column matching list is missing or empty.");
            }
            if (!isset($data['right']) || !is_array($data['right']) || empty($data['right'])) {
                throw new Exception("Right column matching list is missing or empty.");
            }
            if (!isset($data['correct_pairs']) || !is_array($data['correct_pairs'])) {
                throw new Exception("Correct pairing mapping is missing.");
            }

            $leftIds = array_column($data['left'], 'id');
            $rightIds = array_column($data['right'], 'id');

            $pairedLefts = [];
            foreach ($data['correct_pairs'] as $pair) {
                if (!is_array($pair) || count($pair) !== 2) {
                    throw new Exception("Incorrect pair coordinate structure.");
                }
                $leftId = $pair[0];
                $rightId = $pair[1];

                if (!in_array($leftId, $leftIds)) {
                    throw new Exception("Invalid key references in pairing list: " . $leftId);
                }
                if (!in_array($rightId, $rightIds)) {
                    throw new Exception("Invalid value references in pairing list: " . $rightId);
                }
                $pairedLefts[] = $leftId;
            }

            // Validation Rule: every left item must have exactly one entry in correct_pairs
            foreach ($leftIds as $lId) {
                $count = count(array_keys($pairedLefts, $lId));
                if ($count !== 1) {
                    throw new Exception("Each left item must have exactly one matching right option.");
                }
            }
        } elseif ($type === 'matrix_single' || $type === 'matrix_multi') {
            if (!isset($data['rows']) || !is_array($data['rows']) || empty($data['rows'])) {
                throw new Exception("Matrix rows are required and cannot be empty.");
            }
            if (!isset($data['columns']) || !is_array($data['columns']) || empty($data['columns'])) {
                throw new Exception("Matrix columns are required and cannot be empty.");
            }
            if (!isset($data['correct']) || !is_array($data['correct'])) {
                throw new Exception("Matrix correct answers mapping is required.");
            }

            // Check unique IDs for rows and columns
            $rowIds = [];
            foreach ($data['rows'] as $r) {
                if (!isset($r['id']) || !isset($r['label']) || trim($r['label']) === '') {
                    throw new Exception("Each row must have a valid non-empty label.");
                }
                $rowId = trim($r['id']);
                if (in_array($rowId, $rowIds)) {
                    throw new Exception("Duplicate row ID found: " . $rowId);
                }
                $rowIds[] = $rowId;
            }

            $colIds = [];
            foreach ($data['columns'] as $c) {
                if (!isset($c['id']) || !isset($c['label']) || trim($c['label']) === '') {
                    throw new Exception("Each column must have a valid non-empty label.");
                }
                $colId = trim($c['id']);
                if (in_array($colId, $colIds)) {
                    throw new Exception("Duplicate column ID found: " . $colId);
                }
                $colIds[] = $colId;
            }

            // Validate correct mappings
            foreach ($rowIds as $rowId) {
                if (!isset($data['correct'][$rowId]) || !is_array($data['correct'][$rowId])) {
                    throw new Exception("Every row must have a correct column mapping defined.");
                }
                $markedCols = $data['correct'][$rowId];
                if (empty($markedCols)) {
                    throw new Exception("Row '" . $rowId . "' must have at least one correct column marked.");
                }
                if ($type === 'matrix_single' && count($markedCols) !== 1) {
                    throw new Exception("For single select, row '" . $rowId . "' must have exactly one correct column.");
                }
                foreach ($markedCols as $mCol) {
                    if (!in_array($mCol, $colIds)) {
                        throw new Exception("Invalid column ID '" . $mCol . "' marked in row '" . $rowId . "'.");
                    }
                }
            }
        } elseif ($type === 'cloze_dropdown' || $type === 'cloze_dragdrop') {
            if (!isset($data['passage']) || trim($data['passage']) === '') {
                throw new Exception("Passage text is required.");
            }
            if (!isset($data['blanks']) || !is_array($data['blanks']) || empty($data['blanks'])) {
                throw new Exception("At least one blank definition is required.");
            }

            $passage = $data['passage'];
            
            // Find all placeholders matching {{blankId}} in the passage
            preg_match_all('/\{\{([^}]+)\}\}/', $passage, $matches);
            $passageBlankIds = array_map('trim', $matches[1]);
            // Remove duplicates
            $passageBlankIds = array_unique($passageBlankIds);

            $definedBlankIds = [];
            foreach ($data['blanks'] as $idx => $blank) {
                if (!isset($blank['id']) || trim($blank['id']) === '') {
                    throw new Exception("Blank #" . ($idx + 1) . " is missing a unique ID.");
                }
                $blankId = trim($blank['id']);
                if (in_array($blankId, $definedBlankIds)) {
                    throw new Exception("Duplicate blank ID defined: " . $blankId);
                }
                $definedBlankIds[] = $blankId;

                $options = $blank['options'] ?? [];
                if (!is_array($options) || count($options) < 2) {
                    throw new Exception("Blank '" . $blankId . "' must have at least 2 options.");
                }

                $correct = $blank['correct'] ?? '';
                if ($correct === '' || !in_array($correct, $options)) {
                    throw new Exception("Blank '" . $blankId . "' must contain its correct answer inside the options list.");
                }
            }

            // Check every placeholder in passage has matching entry in blanks array
            foreach ($passageBlankIds as $pId) {
                if (!in_array($pId, $definedBlankIds)) {
                    throw new Exception("Placeholder {{" . $pId . "}} is referenced in the passage but has no corresponding blank definition.");
                }
            }

            // Check every defined blank has a corresponding placeholder in passage
            foreach ($definedBlankIds as $dId) {
                if (!in_array($dId, $passageBlankIds)) {
                    throw new Exception("Blank '" . $dId . "' is defined but has no corresponding {{" . $dId . "}} placeholder in the passage.");
                }
            }
        } elseif ($type === 'drag_drop_ordered') {
            if (!isset($data['items']) || !is_array($data['items']) || count($data['items']) < 2) {
                throw new Exception("Ordered drag & drop questions require at least 2 correct sequence items.");
            }
            if (!isset($data['correct_order']) || !is_array($data['correct_order'])) {
                throw new Exception("Correct order definition is required.");
            }

            $itemIds = [];
            foreach ($data['items'] as $idx => $item) {
                if (!isset($item['id']) || trim($item['id']) === '') {
                    throw new Exception("Sequence item #" . ($idx + 1) . " is missing a unique ID.");
                }
                if (!isset($item['text']) || trim($item['text']) === '') {
                    throw new Exception("Sequence item #" . ($idx + 1) . " is missing text.");
                }
                $itemId = trim($item['id']);
                if (in_array($itemId, $itemIds)) {
                    throw new Exception("Duplicate item ID in correct list: " . $itemId);
                }
                $itemIds[] = $itemId;
            }

            $distractorIds = [];
            if (isset($data['distractors']) && is_array($data['distractors'])) {
                foreach ($data['distractors'] as $idx => $dist) {
                    if (!isset($dist['id']) || trim($dist['id']) === '') {
                        throw new Exception("Distractor item #" . ($idx + 1) . " is missing a unique ID.");
                    }
                    if (!isset($dist['text']) || trim($dist['text']) === '') {
                        throw new Exception("Distractor item #" . ($idx + 1) . " is missing text.");
                    }
                    $distId = trim($dist['id']);
                    if (in_array($distId, $itemIds) || in_array($distId, $distractorIds)) {
                        throw new Exception("Duplicate ID in distractors list: " . $distId);
                    }
                    $distractorIds[] = $distId;
                }
            }

            if (count($data['correct_order']) !== count($itemIds)) {
                throw new Exception("Correct order must contain exactly the same number of elements as the items list.");
            }
            foreach ($data['correct_order'] as $cId) {
                if (!in_array($cId, $itemIds)) {
                    throw new Exception("Correct order references invalid or non-existent item: " . $cId);
                }
            }
            if (count(array_unique($data['correct_order'])) !== count($itemIds)) {
                throw new Exception("Correct order contains duplicate or missing items.");
            }

        } elseif ($type === 'highlight') {
            if (!isset($data['passage_html']) || trim($data['passage_html']) === '') {
                throw new Exception("Passage HTML is required for highlight questions.");
            }
            if (!isset($data['segments']) || !is_array($data['segments']) || empty($data['segments'])) {
                throw new Exception("At least one text segment must be defined.");
            }
            if (!isset($data['correct_segment_ids']) || !is_array($data['correct_segment_ids']) || empty($data['correct_segment_ids'])) {
                throw new Exception("At least one correct segment must be marked.");
            }

            $passageHtml = $data['passage_html'];
            $segmentIds = [];
            foreach ($data['segments'] as $idx => $seg) {
                if (!isset($seg['id']) || trim($seg['id']) === '') {
                    throw new Exception("Segment #" . ($idx + 1) . " is missing a unique ID.");
                }
                if (!isset($seg['text']) || trim($seg['text']) === '') {
                    throw new Exception("Segment #" . ($idx + 1) . " is missing text.");
                }

                $segId = trim($seg['id']);
                if (in_array($segId, $segmentIds)) {
                    throw new Exception("Duplicate segment ID found: " . $segId);
                }
                $segmentIds[] = $segId;

                $segText = $seg['text'];
                if (strpos($passageHtml, $segText) === false && strpos(strip_tags($passageHtml), $segText) === false) {
                    throw new Exception("Segment text '" . $segText . "' is not found in the passage.");
                }
            }

            foreach ($data['correct_segment_ids'] as $cId) {
                if (!in_array($cId, $segmentIds)) {
                    throw new Exception("Correct segment ID '" . $cId . "' does not reference a valid segment.");
                }
            }
        }

        return true;
    }

    /**
     * Create a new question
     */
    public function create($data) {
        if (empty($data['question_text'])) {
            throw new Exception("Question text is required.");
        }
        if (empty($data['points']) || !is_numeric($data['points']) || floatval($data['points']) <= 0) {
            throw new Exception("Points must be a valid number greater than 0.");
        }

        $type = $data['type'];
        $qData = $data['question_data'] ?? [];

        // Validate structure
        $this->validateQuestionData($type, $qData);

        // Serialize before insertion
        $serializedData = json_encode($qData, JSON_UNESCAPED_UNICODE);

        $stmt = $this->db->prepare("INSERT INTO questions (category_id, case_id, case_order, type, question_text, question_data, difficulty, points, scoring_method, status, created_by)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['category_id'],
            $data['case_id'] ?? null,
            $data['case_order'] ?? null,
            $type,
            $data['question_text'],
            $serializedData,
            $data['difficulty'],
            $data['points'],
            $data['scoring_method'] ?? 'all_or_nothing',
            $data['status'] ?? 'draft',
            $data['created_by'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Update a question
     */
    public function update($id, $data) {
        if (empty($data['question_text'])) {
            throw new Exception("Question text is required.");
        }
        if (empty($data['points']) || !is_numeric($data['points']) || floatval($data['points']) <= 0) {
            throw new Exception("Points must be a valid number greater than 0.");
        }

        $type = $data['type'];
        $qData = $data['question_data'] ?? [];

        $this->validateQuestionData($type, $qData);
        $serializedData = json_encode($qData, JSON_UNESCAPED_UNICODE);

        $stmt = $this->db->prepare("UPDATE questions 
                                    SET category_id = ?, case_id = ?, case_order = ?, type = ?, question_text = ?, question_data = ?, difficulty = ?, points = ?, scoring_method = ?, status = ?
                                    WHERE id = ?");
        return $stmt->execute([
            $data['category_id'],
            $data['case_id'] ?? null,
            $data['case_order'] ?? null,
            $type,
            $data['question_text'],
            $serializedData,
            $data['difficulty'],
            $data['points'],
            $data['scoring_method'] ?? 'all_or_nothing',
            $data['status'] ?? 'draft',
            $id
        ]);
    }

    /**
     * Delete a question
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM questions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Attach a question to a case study with order index
     */
    public function attachToCase($questionId, $caseId, $orderIndex) {
        $stmt = $this->db->prepare("UPDATE questions SET case_id = ?, case_order = ? WHERE id = ?");
        return $stmt->execute([$caseId, $orderIndex, $questionId]);
    }

    /**
     * Detach a question from a case study
     */
    public function detachFromCase($questionId) {
        $stmt = $this->db->prepare("UPDATE questions SET case_id = NULL, case_order = NULL WHERE id = ?");
        return $stmt->execute([$questionId]);
    }
}
