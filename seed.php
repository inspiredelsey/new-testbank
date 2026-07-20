<?php
/**
 * Test Bank LMS - Complete High-Fidelity Database Seeder
 * Populates all tables with 5+ highly realistic domain-specific records.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/testbank/includes/Database.php';
    $db = Database::getInstance()->getConnection();

    echo "Starting Database Seeding...\n";

    // 1. Disable constraints to allow a clean purge
    $db->exec("PRAGMA foreign_keys = OFF;");

    // Clean existing records (preserving main admin user ID = 1)
    $db->exec("DELETE FROM users WHERE id > 1;");
    $db->exec("DELETE FROM categories;");
    $db->exec("DELETE FROM courses;");
    $db->exec("DELETE FROM course_enrollments;");
    $db->exec("DELETE FROM cases;");
    $db->exec("DELETE FROM case_exhibits;");
    $db->exec("DELETE FROM questions;");
    $db->exec("DELETE FROM question_options;");
    $db->exec("DELETE FROM exams;");
    $db->exec("DELETE FROM exam_questions;");
    $db->exec("DELETE FROM exam_rules;");
    $db->exec("DELETE FROM documents;");
    $db->exec("DELETE FROM links;");
    $db->exec("DELETE FROM learning_path_items;");
    $db->exec("DELETE FROM learning_path_progress;");
    $db->exec("DELETE FROM exam_attempts;");
    $db->exec("DELETE FROM attempt_answers;");

    $db->exec("PRAGMA foreign_keys = ON;");
    echo "Purged existing test data successfully.\n";

    // ==========================================
    // 2. Insert Categories (6 records)
    // ==========================================
    $categories = [
        [1, null, "General Science", "general-science", "Basic foundational science courses"],
        [2, null, "Web Development", "web-development", "HTML, CSS, JavaScript, PHP, and modern web architectures"],
        [3, null, "Cardiology Nursing", "cardiology-nursing", "Advanced cardiac care, clinical nursing indicators, and NGN clinical items"],
        [4, null, "Pharmacology", "pharmacology", "Medication dosages, pharmacokinetics, safety protocols, and medication calculations"],
        [5, null, "Database Systems", "database-systems", "Relational database designs, query optimization, and schema constraints"],
        [6, null, "Pediatric Medicine", "pediatric-medicine", "Pediatric critical care, disease symptoms, and clinical assessment parameters"]
    ];

    $stmt = $db->prepare("INSERT INTO categories (id, parent_id, name, slug, description) VALUES (?, ?, ?, ?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }
    echo "Seeded 6 Categories.\n";

    // ==========================================
    // 3. Insert Users (1 instructor, 5 students)
    // ==========================================
    $instructorPassword = password_hash('instructor123', PASSWORD_DEFAULT);
    $studentPassword = password_hash('student123', PASSWORD_DEFAULT);

    // Instructor ID = 2
    $db->prepare("INSERT INTO users (id, name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?)")
       ->execute([2, 'Instructor Jones', 'instructor@testbank.com', $instructorPassword, 'instructor', 'active']);

    // Students IDs = 3, 4, 5, 6, 7
    $students = [
        [3, 'Student Alice', 'alice@testbank.com', $studentPassword, 'student', 'active'],
        [4, 'Student Bob', 'bob@testbank.com', $studentPassword, 'student', 'active'],
        [5, 'Student Charlie', 'charlie@testbank.com', $studentPassword, 'student', 'active'],
        [6, 'Student Diana', 'diana@testbank.com', $studentPassword, 'student', 'active'],
        [7, 'Student Ethan', 'ethan@testbank.com', $studentPassword, 'student', 'active']
    ];

    $stmt = $db->prepare("INSERT INTO users (id, name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($students as $stu) {
        $stmt->execute($stu);
    }
    echo "Seeded 1 Instructor and 5 Student users.\n";

    // ==========================================
    // 4. Insert Courses (5 published records)
    // ==========================================
    $courses = [
        [1, "Introduction to HTML5 and CSS3", "Learn the essential building blocks of web design, styling, box models, responsive layout engines, and semantic tagging hierarchies.", 2, 2, "published", 70.00],
        [2, "Advanced Clinical Pharmacology & Therapeutics", "Comprehensive dosage calculations, drug interactions, loop diuretics, beta-blocker administration guidelines, and patient safety protocols.", 4, 2, "published", 75.00],
        [3, "Clinical Electrocardiogram Interpretation", "A diagnostic masterclass exploring sinus rhythms, bundle branch blocks, myocardial infarctions, dysrhythmias, and clinical nursing interventions.", 3, 2, "published", 80.00],
        [4, "Relational Database Design with SQL", "Master primary keys, foreign key constraints, 1NF/2NF/3NF database normalization, indexing pipelines, and query optimizations.", 5, 2, "published", 70.00],
        [5, "Human Anatomy and Physiology Foundations", "An in-depth review of human organ systems, focusing on cardiopulmonary anatomy, respiratory distress triggers, and biological feedback loops.", 1, 2, "published", 60.00]
    ];

    $stmt = $db->prepare("INSERT INTO courses (id, title, description, category_id, instructor_id, status, pass_percentage) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($courses as $crs) {
        $stmt->execute($crs);
    }
    echo "Seeded 5 Courses.\n";

    // ==========================================
    // 5. Insert Course Enrollments (8 records)
    // ==========================================
    $enrollments = [
        [1, 3], // Alice -> Course 1
        [2, 3], // Alice -> Course 2
        [3, 3], // Alice -> Course 3
        [1, 4], // Bob -> Course 1
        [4, 4], // Bob -> Course 4
        [2, 5], // Charlie -> Course 2
        [3, 6], // Diana -> Course 3
        [5, 7]  // Ethan -> Course 5
    ];

    $stmt = $db->prepare("INSERT INTO course_enrollments (course_id, student_id) VALUES (?, ?)");
    foreach ($enrollments as $enr) {
        $stmt->execute($enr);
    }
    echo "Seeded 8 Student Enrollments.\n";

    // ==========================================
    // 6. Insert NGN Cases (5 Case Studies)
    // ==========================================
    $cases = [
        [1, "Acute Decompensated Heart Failure", "A 68-year-old male with a history of hypertension presents with progressive dyspnea on exertion, bilateral 3+ pitting pedal edema, and orthopnea requiring sleeping in a recliner chair. On clinical assessment, heart rate is 108 bpm, respiratory rate is 24/min, blood pressure is 152/94 mmHg, and SpO2 is 89% on room air. Crackles are heard in bilateral lung bases.", 3, 0],
        [2, "Post-Operative Pulmonary Embolism", "A 45-year-old female patient 2 days post-total hip arthroplasty suddenly complains of sharp, pleuritic chest pain and severe shortness of breath. On clinical evaluation, she appears anxious, with diaphoresis, heart rate of 115 bpm, respiratory rate of 28/min, and blood pressure of 105/65 mmHg. Her right calf is warm, erythematous, and tender on palpation.", 3, 0],
        [3, "Pediatric Asthma Exacerbation", "A 7-year-old female with a history of persistent asthma is brought to the Emergency Department by her mother. The mother notes the child has had a persistent dry cough, chest tightness, and a high respiratory rate for the past 6 hours. On assessment, the child is using intercostal accessory muscles, has audible wheezing on expiration, and an SpO2 of 91% on room air.", 6, 0],
        [4, "Acute Acetaminophen Toxicity", "An 18-year-old female is admitted to the medical-surgical unit after consuming an unknown quantity of acetaminophen tablets approximately 8 hours ago in a self-harm attempt. The patient complains of right upper quadrant abdominal tenderness, nausea, and mild lethargy. Liver function enzymes (AST/ALT) are significantly elevated.", 4, 0],
        [5, "Type 2 Diabetic Ketoacidosis (DKA)", "A 34-year-old male with a history of type 2 diabetes presents with extreme fatigue, nausea, vomiting, abdominal pain, and deep, rapid (Kussmaul) respirations. His breath has a distinct fruity odor. Fingerstick glucose is 480 mg/dL, and arterial blood gas reveals a pH of 7.21 with positive serum ketones.", 4, 0]
    ];

    $stmt = $db->prepare("INSERT INTO cases (id, title, scenario_text, category_id, is_trend) VALUES (?, ?, ?, ?, ?)");
    foreach ($cases as $cs) {
        $stmt->execute($cs);
    }
    echo "Seeded 5 NGN Case Studies.\n";

    // ==========================================
    // 7. Insert Questions (11 realistic items of various types)
    // ==========================================
    $questions = [
        // Question 1: mcq_single (Loop Diuretic)
        [
            1, 4, 'mcq_single', "Which of the following medications is classified as a loop diuretic?", 'easy', 1.00, 'published', 2, null, null,
            json_encode([
                "options" => [
                    ["id" => "opt1", "text" => "Furosemide", "is_correct" => true],
                    ["id" => "opt2", "text" => "Hydrochlorothiazide", "is_correct" => false],
                    ["id" => "opt3", "text" => "Spironolactone", "is_correct" => false],
                    ["id" => "opt4", "text" => "Metolazone", "is_correct" => false]
                ]
            ])
        ],
        // Question 2: mcq_multi_sata (Left-sided Heart Failure SATA)
        [
            2, 3, 'mcq_multi_sata', "Select all signs and symptoms that are commonly associated with Left-sided Heart Failure. (Select All That Apply)", 'medium', 2.00, 'published', 2, null, null,
            json_encode([
                "options" => [
                    ["id" => "opt1", "text" => "Orthopnea", "is_correct" => true],
                    ["id" => "opt2", "text" => "Crackles in lung bases", "is_correct" => true],
                    ["id" => "opt3", "text" => "Bilateral jugular venous distention (JVD)", "is_correct" => false],
                    ["id" => "opt4", "text" => "Dyspnea on exertion", "is_correct" => true],
                    ["id" => "opt5", "text" => "Splenomegaly", "is_correct" => false]
                ]
            ])
        ],
        // Question 3: true_false (CSS box-sizing)
        [
            3, 2, 'true_false', "In CSS, the 'box-sizing: border-box' property includes padding and border in the element's total width and height.", 'easy', 1.00, 'published', 2, null, null,
            json_encode([
                "options" => [
                    ["id" => "opt1", "text" => "True", "is_correct" => true],
                    ["id" => "opt2", "text" => "False", "is_correct" => false]
                ]
            ])
        ],
        // Question 4: matching (Database Concepts Matching)
        [
            4, 5, 'matching', "Match each database concept with its appropriate definition.", 'medium', 3.00, 'published', 2, null, null,
            json_encode([
                "left" => [
                    ["id" => "l1", "text" => "Primary Key"],
                    ["id" => "l2", "text" => "Foreign Key"],
                    ["id" => "l3", "text" => "Unique Constraint"]
                ],
                "right" => [
                    ["id" => "r1", "text" => "Uniquely identifies each record in a table and cannot be NULL."],
                    ["id" => "r2", "text" => "A field in one table that refers to the Primary Key of another table."],
                    ["id" => "r3", "text" => "Ensures all values in a column are distinct, but can accept NULL values."]
                ],
                "correct_pairs" => [
                    ["l1", "r1"],
                    ["l2", "r2"],
                    ["l3", "r3"]
                ]
            ])
        ],
        // Question 5: cloze_dropdown (Cardiovascular Therapy Passage)
        [
            5, 4, 'cloze_dropdown', "Fill in the clinical passage regarding cardiovascular therapy by selecting the correct option dropdowns.", 'hard', 2.00, 'published', 2, null, null,
            json_encode([
                "passage" => "When administering Nitroglycerin to a patient experiencing chest pain, the nurse must monitor for {{blank1}} as a common side effect. If the patient's systolic blood pressure falls below {{blank2}} mmHg, the infusion must be held.",
                "blanks" => [
                    [
                        "id" => "blank1",
                        "options" => ["Headache", "Bradycardia", "Hypertension", "Constipation"],
                        "correct" => "Headache"
                    ],
                    [
                        "id" => "blank2",
                        "options" => ["120", "100", "90", "80"],
                        "correct" => "90"
                    ]
                ]
            ])
        ],
        // Question 6: cloze_dragdrop (Browser rendering)
        [
            6, 2, 'cloze_dragdrop', "Complete the statement regarding modern web browser rendering pipelines by dragging the tokens.", 'hard', 2.00, 'published', 2, null, null,
            json_encode([
                "passage" => "The browser parses HTML to construct the {{blank1}} tree, and parses CSS to construct the {{blank2}} tree. These are then combined to form the Render Tree.",
                "blanks" => [
                    [
                        "id" => "blank1",
                        "options" => ["DOM", "SAX", "CSSOM", "AST"],
                        "correct" => "DOM"
                    ],
                    [
                        "id" => "blank2",
                        "options" => ["CSSOM", "DOM", "SASS", "LESS"],
                        "correct" => "CSSOM"
                    ]
                ]
            ])
        ],
        // Question 7: drag_drop_ordered (IVPB Admin Sequence)
        [
            7, 3, 'drag_drop_ordered', "Place the following clinical steps for administering an intravenous piggyback (IVPB) medication in the correct chronological sequence from first to last.", 'medium', 3.00, 'published', 2, null, null,
            json_encode([
                "items" => [
                    ["id" => "item1", "text" => "Verify the patient's identity using two identifiers and check the medication label against the MAR."],
                    ["id" => "item2", "text" => "Assess the IV site for signs of infiltration, phlebitis, or infection."],
                    ["id" => "item3", "text" => "Clamp the IVPB tubing and spike the medication bag using sterile technique."],
                    ["id" => "item4", "text" => "Program the IV infusion pump with the correct rate and volume to be infused."]
                ],
                "correct_order" => ["item1", "item2", "item3", "item4"],
                "distractors" => [
                    ["id" => "dist1", "text" => "Dispose of the IV cannula in the hazardous sharps container immediately."]
                ]
            ])
        ],
        // Question 8: matrix_single (Right vs Left Heart Failure findings)
        [
            8, 3, 'matrix_single', "For each of the patient assessment findings listed below, indicate whether it is most characteristic of Right-sided Heart Failure or Left-sided Heart Failure.", 'medium', 3.00, 'published', 2, null, null,
            json_encode([
                "rows" => [
                    ["id" => "row1", "label" => "Pulmonary congestion and rales"],
                    ["id" => "row2", "label" => "Peripheral edema and hepatomegaly"],
                    ["id" => "row3", "label" => "Jugular venous distention (JVD)"],
                    ["id" => "row4", "label" => "Dyspnea and orthopnea"]
                ],
                "columns" => [
                    ["id" => "col1", "label" => "Left-sided HF"],
                    ["id" => "col2", "label" => "Right-sided HF"]
                ],
                "correct" => [
                    "row1" => ["col1"],
                    "row2" => ["col2"],
                    "row3" => ["col2"],
                    "row4" => ["col1"]
                ]
            ])
        ],
        // Question 9: bowtie (Acute Decompensated Pulmonary Edema case)
        [
            9, 3, 'bowtie', "Analyze the patient presentation and select the appropriate nursing interventions, most likely diagnosis, and parameters to monitor.", 'hard', 5.00, 'published', 2, null, null,
            json_encode([
                "left_options" => [
                    ["id" => "l1", "text" => "Administer high-flow supplemental oxygen"],
                    ["id" => "l2", "text" => "Administer IV Furosemide immediately"],
                    ["id" => "l3", "text" => "Initiate IV normal saline bolus of 1 Liter"]
                ],
                "center_options" => [
                    ["id" => "c1", "text" => "Acute Pulmonary Edema"],
                    ["id" => "c2", "text" => "Hypovolemic Shock"],
                    ["id" => "c3", "text" => "Acute Renal Failure"]
                ],
                "right_options" => [
                    ["id" => "r1", "text" => "Monitor oxygen saturation & respiratory rate"],
                    ["id" => "r2", "text" => "Monitor hourly urinary output"],
                    ["id" => "r3", "text" => "Monitor deep tendon reflexes"]
                ],
                "left_target_count" => 2,
                "center_target_count" => 1,
                "right_target_count" => 2,
                "correct" => [
                    "left" => ["l1", "l2"],
                    "center" => ["c1"],
                    "right" => ["r1", "r2"]
                ]
            ])
        ],
        // Question 10: fill_blank_calc (Drip Rate Calculation)
        [
            10, 4, 'fill_blank_calc', "An order reads: Infuse 1,000 mL of 0.9% Normal Saline over 8 hours. The tubing drop factor is 15 gtt/mL. What is the required infusion rate in drops per minute (gtt/min)? (Round to the nearest whole number)", 'medium', 2.00, 'published', 2, null, null,
            json_encode([
                "correct_value" => "31",
                "tolerance" => "0",
                "unit" => "gtt/min"
            ])
        ],
        // Question 11: essay (Handoff SBAR Essay)
        [
            11, 3, 'essay', "Construct a brief handoff report using the SBAR (Situation, Background, Assessment, Recommendation) format for a patient recently diagnosed with acute cardiovascular distress.", 'medium', 4.00, 'published', 2, null, null,
            json_encode([])
        ]
    ];

    $stmt = $db->prepare("INSERT INTO questions (id, category_id, type, question_text, difficulty, points, status, created_by, case_id, case_order, question_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($questions as $q) {
        $stmt->execute($q);
    }
    echo "Seeded 11 High-Fidelity NGN and Standard Questions.\n";

    // Insert options in the helper `question_options` table for backward compatibility/reports
    // MCQ Single
    $db->prepare("INSERT INTO question_options (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)")->execute([1, 'Furosemide', 1, 0]);
    $db->prepare("INSERT INTO question_options (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)")->execute([1, 'Hydrochlorothiazide', 0, 1]);
    $db->prepare("INSERT INTO question_options (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)")->execute([1, 'Spironolactone', 0, 2]);
    $db->prepare("INSERT INTO question_options (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)")->execute([1, 'Metolazone', 0, 3]);

    // MCQ SATA
    $db->prepare("INSERT INTO question_options (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)")->execute([2, 'Orthopnea', 1, 0]);
    $db->prepare("INSERT INTO question_options (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)")->execute([2, 'Crackles in lung bases', 1, 1]);
    $db->prepare("INSERT INTO question_options (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)")->execute([2, 'Bilateral jugular venous distention (JVD)', 0, 2]);
    $db->prepare("INSERT INTO question_options (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)")->execute([2, 'Dyspnea on exertion', 1, 3]);
    $db->prepare("INSERT INTO question_options (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)")->execute([2, 'Splenomegaly', 0, 4]);

    // True/False
    $db->prepare("INSERT INTO question_options (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)")->execute([3, 'True', 1, 0]);
    $db->prepare("INSERT INTO question_options (question_id, option_text, is_correct, order_index) VALUES (?, ?, ?, ?)")->execute([3, 'False', 0, 1]);

    echo "Seeded question helper options.\n";

    // ==========================================
    // 8. Insert Exams (5 published exams)
    // ==========================================
    $exams = [
        [1, "Web Development Essentials Quiz", "A test covering fundamental concepts of HTML5 semantic structure, CSS box-sizing properties, and browser rendering architectures.", 2, 1, 30, 70.00, 1],
        [2, "Advanced Pharmacology Midterm", "High-stakes midterm assessing knowledge of cardiac drugs, loop diuretics, and pediatric IV infusion calculations.", 4, 2, 60, 75.00, 0],
        [3, "Electrocardiogram Fundamentals Exam", "Testing interpretation of left-sided heart failure clinical presentation, patient positioning, and acute interventions.", 3, 3, 45, 80.00, 1],
        [4, "SQL Database Schema Design Test", "Evaluates normalization levels, constraint specifications, and key mappings.", 5, 4, 50, 70.00, 0],
        [5, "General Science Concepts Evaluation", "Assessing biological loops, human organ systems, and chemical behaviors.", 1, 5, 40, 60.00, 1]
    ];

    $stmt = $db->prepare("INSERT INTO exams (id, title, description, category_id, course_id, duration_minutes, pass_percentage, shuffle_questions, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published', 2)");
    foreach ($exams as $ex) {
        $stmt->execute($ex);
    }
    echo "Seeded 5 Exams.\n";

    // ==========================================
    // 9. Map Questions to Exams (exam_questions)
    // ==========================================
    $examQuestions = [
        // Exam 1 (Web Dev): Question 3 (True/False), Question 6 (Cloze DragDrop)
        [1, 3, 1],
        [1, 6, 2],

        // Exam 2 (Pharm): Question 1 (Loop), Question 5 (Cloze Dropdown), Question 10 (Calc)
        [2, 1, 1],
        [2, 5, 2],
        [2, 10, 3],

        // Exam 3 (ECG): Question 2 (SATA), Question 7 (Ordered), Question 8 (Matrix), Question 9 (Bowtie), Question 11 (Essay)
        [3, 2, 1],
        [3, 7, 2],
        [3, 8, 3],
        [3, 9, 4],
        [3, 11, 5],

        // Exam 4 (SQL): Question 4 (Matching)
        [4, 4, 1]
    ];

    $stmt = $db->prepare("INSERT INTO exam_questions (exam_id, question_id, order_index) VALUES (?, ?, ?)");
    foreach ($examQuestions as $eq) {
        $stmt->execute($eq);
    }
    echo "Mapped Questions to Exams (exam_questions).\n";

    // ==========================================
    // 10. Insert Documents (5 records)
    // ==========================================
    $documents = [
        [1, 1, "HTML5 Semantic Elements Reference Guide", "uploads/documents/html5_reference.pdf", "pdf", "Quick cheat-sheet reviewing <header>, <article>, and other tags.", "published", 1],
        [2, 2, "Common Cardiovascular Drug Infusion Protocols", "uploads/documents/cardio_drugs.pdf", "pdf", "Step-by-step guidance on Dobutamine and Nitroglycerin rates.", "published", 1],
        [3, 3, "Introduction to 12-Lead ECG Placement Diagrams", "uploads/documents/ecg_placement.pdf", "pdf", "Graphic layouts indicating precordial and limb lead positions.", "published", 1],
        [4, 4, "Database Normalization Crib Sheet (1NF, 2NF, 3NF)", "uploads/documents/db_normalization.pdf", "pdf", "Concise criteria definitions for relational normal forms.", "published", 1],
        [5, 5, "Human Respiratory System Anatomy Review", "uploads/documents/anatomy_respiratory.pdf", "pdf", "Foundational study notes on alveolar ventilation and blood gas exchange.", "published", 1]
    ];

    $stmt = $db->prepare("INSERT INTO documents (id, course_id, title, file_path, file_type, description, status, order_index) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($documents as $doc) {
        $stmt->execute($doc);
    }
    echo "Seeded 5 Course Documents.\n";

    // ==========================================
    // 11. Insert Links (5 records)
    // ==========================================
    $links = [
        [1, 1, "W3C HTML Living Standard Specification", "https://html.spec.whatwg.org/", "Official HTML living specification document", 1],
        [2, 2, "FDA Approved Labels and Medications Lookup", "https://labels.fda.gov/", "Lookup standard FDA medical dosage label lists", 1],
        [3, 3, "American Heart Association ECG Guidelines", "https://www.heart.org/", "Guidelines for cardiac electrical analysis and placements", 1],
        [4, 4, "SQL Fiddle Interactive SQLite Playground", "http://sqlfiddle.com/", "Online SQL tester for query construction and testing", 1],
        [5, 5, "Visible Body Anatomy and Physiology Free Learning Portal", "https://www.visiblebody.com/", "Anatomy systems descriptions and visualizations", 1]
    ];

    $stmt = $db->prepare("INSERT INTO links (id, course_id, title, url, description, order_index) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($links as $lnk) {
        $stmt->execute($lnk);
    }
    echo "Seeded 5 Course Links.\n";

    // ==========================================
    // 12. Insert Learning Path Items (7 items total)
    // ==========================================
    $lpItems = [
        // Course 1 path
        [1, 1, "document", 1, null, 1], // HTML5 ref doc
        [2, 1, "link", 1, 1, 2],        // W3C spec link
        [3, 1, "quiz", 1, 2, 3],        // Web Dev quiz

        // Course 2 path
        [4, 2, "document", 2, null, 1], // Cardio drugs doc
        [5, 2, "quiz", 2, 4, 2],        // Pharm midterm

        // Course 3 path
        [6, 3, "document", 3, null, 1], // Placement doc
        [7, 3, "quiz", 3, 6, 2]         // ECG exam
    ];

    $stmt = $db->prepare("INSERT INTO learning_path_items (id, course_id, item_type, item_id, prerequisite_item_id, order_index) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($lpItems as $lpi) {
        $stmt->execute($lpi);
    }
    echo "Seeded 7 Learning Path Items across courses.\n";

    echo "\nDatabase Seeding COMPLETED SUCCESSFULLY!\n";

} catch (Exception $e) {
    echo "SEEDING ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
