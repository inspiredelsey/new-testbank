<?php
/**
 * Question Renderer - Test Bank LMS
 * Handles rendering the interactive/preview templates for various question types,
 * including NGN cases and standard items.
 */

class QuestionRenderer {

    /**
     * Primary render method.
     * If $options is null, it operates in admin/instructor authoring PREVIEW mode,
     * utilizing $question['question_data'] array directly.
     * Otherwise, it delegates to the student exam-taking interactive renderer.
     */
    public static function render($question, $options = null, $userAnswer = null, $disabled = false) {
        // Fallback/Delegation to student exam-taking view if options are provided externally
        if ($options !== null) {
            return self::renderStudentTaking($question, $options, $userAnswer, $disabled);
        }

        // Admin/Instructor Authoring Preview Mode
        $type = $question['type'];
        $qData = $question['question_data'] ?? [];

        $html = "<div class='card border-0 shadow-sm rounded-3 overflow-hidden mb-3'>";
        
        // Header
        $html .= "  <div class='card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2'>";
        $html .= "    <div class='d-flex align-items-center gap-2'>";
        $html .= "      <span class='badge bg-primary text-white font-sans px-2.5 py-1.5' style='font-size: 0.75rem;'>" . self::getTypeLabel($type) . "</span>";
        $html .= "      <span class='text-muted small font-sans'>(" . floatval($question['points'] ?? 1.00) . " pts)</span>";
        $html .= "    </div>";
        $difficultyClass = self::getDifficultyBadgeClass($question['difficulty'] ?? 'medium');
        $html .= "    <span class='badge " . $difficultyClass . " font-sans text-capitalize'>" . htmlspecialchars($question['difficulty'] ?? 'medium') . "</span>";
        $html .= "  </div>";

        // Body
        $html .= "  <div class='card-body p-4'>";
        $html .= "    <div class='fs-5 fw-medium text-dark font-sans mb-4' style='white-space: pre-wrap;'>" . htmlspecialchars($question['question_text'] ?? '') . "</div>";
        $html .= "    <div class='question-preview-content'>";

        switch ($type) {
            case 'mcq_single':
            case 'mcq_multi_sata':
                $opts = $qData['options'] ?? [];
                $inputType = ($type === 'mcq_single') ? 'radio' : 'checkbox';
                
                $html .= "    <div class='d-flex flex-column gap-2'>";
                foreach ($opts as $idx => $opt) {
                    $isCorrect = !empty($opt['is_correct']);
                    $bgClass = $isCorrect ? 'bg-success-subtle border-success-subtle text-success-emphasis' : 'bg-light border-light-subtle text-dark';
                    $icon = $isCorrect ? '<i data-lucide="check-circle" class="text-success" size="18"></i>' : '<i data-lucide="circle" class="text-muted" size="18"></i>';
                    
                    $html .= "      <div class='p-3 rounded-3 border d-flex align-items-center justify-content-between gap-3 " . $bgClass . "'>";
                    $html .= "        <div class='d-flex align-items-center gap-3'>";
                    $html .= "          <input class='form-check-input mt-0' type='" . $inputType . "' disabled " . ($isCorrect ? 'checked' : '') . " style='pointer-events: none;'>";
                    $html .= "          <span class='font-sans'>" . htmlspecialchars($opt['text'] ?? '') . "</span>";
                    $html .= "        </div>";
                    $html .= "        <div>" . $icon . "</div>";
                    $html .= "      </div>";
                }
                $html .= "    </div>";
                break;

            case 'true_false':
                $opts = $qData['options'] ?? [];
                $html .= "    <div class='d-flex flex-column gap-2'>";
                foreach ($opts as $opt) {
                    $isCorrect = !empty($opt['is_correct']);
                    $bgClass = $isCorrect ? 'bg-success-subtle border-success-subtle text-success-emphasis' : 'bg-light border-light-subtle text-dark';
                    $icon = $isCorrect ? '<i data-lucide="check-circle" class="text-success" size="18"></i>' : '<i data-lucide="circle" class="text-muted" size="18"></i>';
                    
                    $html .= "      <div class='p-3 rounded-3 border d-flex align-items-center justify-content-between gap-3 " . $bgClass . "'>";
                    $html .= "        <div class='d-flex align-items-center gap-3'>";
                    $html .= "          <input class='form-check-input mt-0' type='radio' disabled " . ($isCorrect ? 'checked' : '') . " style='pointer-events: none;'>";
                    $html .= "          <span class='font-sans fw-semibold'>" . htmlspecialchars($opt['text'] ?? '') . "</span>";
                    $html .= "        </div>";
                    $html .= "        <div>" . $icon . "</div>";
                    $html .= "      </div>";
                }
                $html .= "    </div>";
                break;

            case 'matching':
                $left = $qData['left'] ?? [];
                $right = $qData['right'] ?? [];
                $pairs = $qData['correct_pairs'] ?? [];

                // Create helper map of right column items
                $rightMap = [];
                foreach ($right as $rItem) {
                    $rightMap[$rItem['id']] = $rItem['text'];
                }

                // Create map of left -> right pairs
                $pairMap = [];
                foreach ($pairs as $p) {
                    $pairMap[$p[0]] = $p[1];
                }

                $html .= "    <div class='table-responsive border rounded-3'>";
                $html .= "      <table class='table table-hover align-middle mb-0 font-sans'>";
                $html .= "        <thead class='table-light text-muted small uppercase'>";
                $html .= "          <tr>";
                $html .= "            <th style='width: 45%;'>Concept (Left Side)</th>";
                $html .= "            <th style='width: 10%;' class='text-center'>Match</th>";
                $html .= "            <th style='width: 45%;'>Correct Statement / Term (Right Side)</th>";
                $html .= "          </tr>";
                $html .= "        </thead>";
                $html .= "        <tbody>";
                
                foreach ($left as $lItem) {
                    $matchedRightId = $pairMap[$lItem['id']] ?? '';
                    $matchedRightText = $rightMap[$matchedRightId] ?? '<span class="text-danger fw-semibold">(No Match Configured)</span>';
                    
                    $html .= "          <tr>";
                    $html .= "            <td class='p-3 fw-medium text-dark'>" . htmlspecialchars($lItem['text'] ?? '') . "</td>";
                    $html .= "            <td class='p-3 text-center'><i data-lucide=" . ($matchedRightId ? "'arrow-right'" : "'alert-triangle'") . " class='text-primary' size='18'></i></td>";
                    $html .= "            <td class='p-3 bg-success-subtle text-success-emphasis fw-medium'>" . htmlspecialchars($matchedRightText) . "</td>";
                    $html .= "          </tr>";
                }

                $html .= "        </tbody>";
                $html .= "      </table>";
                $html .= "    </div>";
                break;

            case 'matrix_single':
            case 'matrix_multi':
                $rows = $qData['rows'] ?? [];
                $columns = $qData['columns'] ?? [];
                $correct = $qData['correct'] ?? [];
                $isMulti = ($type === 'matrix_multi');
                $inputType = $isMulti ? 'checkbox' : 'radio';

                $html .= "    <div class='table-responsive border rounded-3'>";
                $html .= "      <table class='table table-bordered align-middle text-center mb-0 font-sans'>";
                $html .= "        <thead class='table-light text-muted small uppercase'>";
                $html .= "          <tr>";
                $html .= "            <th class='text-start' style='width: 40%;'>Findings / Rows</th>";
                foreach ($columns as $col) {
                    $html .= "        <th>" . htmlspecialchars($col['label'] ?? '') . "</th>";
                }
                $html .= "          </tr>";
                $html .= "        </thead>";
                $html .= "        <tbody>";
                foreach ($rows as $row) {
                    $rowId = $row['id'] ?? '';
                    $rowCorrectCols = $correct[$rowId] ?? [];
                    $html .= "          <tr>";
                    $html .= "            <td class='text-start fw-medium text-dark'>" . htmlspecialchars($row['label'] ?? '') . "</td>";
                    foreach ($columns as $col) {
                        $colId = $col['id'] ?? '';
                        $isCellCorrect = in_array($colId, $rowCorrectCols);
                        $cellBg = $isCellCorrect ? 'bg-success-subtle text-success-emphasis' : '';
                        $html .= "          <td class='" . $cellBg . "'>";
                        $inputName = "preview_matrix_" . htmlspecialchars($rowId);
                        $html .= "            <input class='form-check-input mt-0' type='" . $inputType . "' name='" . $inputName . ($isMulti ? '[]' : '') . "' disabled " . ($isCellCorrect ? 'checked' : '') . " style='pointer-events: none; transform: scale(1.1);'>";
                        $html .= "          </td>";
                    }
                    $html .= "          </tr>";
                }
                $html .= "        </tbody>";
                $html .= "      </table>";
                $html .= "    </div>";
                break;

            case 'cloze_dropdown':
            case 'cloze_dragdrop':
                $passage = $qData['passage'] ?? '';
                $blanks = $qData['blanks'] ?? [];

                $escapedPassage = htmlspecialchars($passage);

                $blanksMap = [];
                foreach ($blanks as $blank) {
                    $blanksMap[$blank['id']] = $blank;
                }

                $renderedPassage = preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($blanksMap, $type) {
                    $blankId = trim($matches[1]);
                    if (!isset($blanksMap[$blankId])) {
                        return "<span class='badge bg-danger'>{{ " . htmlspecialchars($blankId) . " (Missing) }}</span>";
                    }

                    $blank = $blanksMap[$blankId];
                    $correctVal = $blank['correct'] ?? '';
                    $options = $blank['options'] ?? [];

                    if ($type === 'cloze_dropdown') {
                        $selectHtml = "<select class='form-select form-select-sm d-inline-block w-auto border-success text-success-emphasis fw-medium' disabled style='pointer-events: none; margin: 0 4px; background-color: var(--bs-success-bg-subtle, #d1e7dd);'>";
                        foreach ($options as $opt) {
                            $isCorrect = ($opt === $correctVal);
                            $selectHtml .= "<option " . ($isCorrect ? 'selected' : '') . ">" . htmlspecialchars($opt) . "</option>";
                        }
                        $selectHtml .= "</select>";
                        return $selectHtml;
                    } else {
                        // cloze_dragdrop
                        return " <span class='badge bg-success-subtle border border-success text-success-emphasis px-2.5 py-1.5 font-sans fs-6 rounded-3 fw-semibold' style='margin: 0 4px; display: inline-flex; align-items: center; gap: 6px;'><i data-lucide='grip-vertical' class='text-success' style='width: 14px; height: 14px;'></i>" . htmlspecialchars($correctVal) . "</span> ";
                    }
                }, $escapedPassage);

                $html .= "    <div class='p-3 bg-light rounded-3 font-sans border' style='line-height: 1.8; font-size: 1.1rem; white-space: pre-wrap;'>";
                $html .= $renderedPassage;
                $html .= "    </div>";

                if ($type === 'cloze_dragdrop') {
                    $html .= "    <div class='mt-4'>";
                    $html .= "      <div class='text-muted small uppercase mb-2 font-sans fw-semibold' style='font-size: 0.75rem; letter-spacing: 0.5px;'>Draggable Option Pool (Correct highlighted):</div>";
                    $html .= "      <div class='d-flex flex-wrap gap-2'>";
                    foreach ($blanks as $blank) {
                        $correctVal = $blank['correct'] ?? '';
                        foreach ($blank['options'] ?? [] as $opt) {
                            $isCorrect = ($opt === $correctVal);
                            $bgClass = $isCorrect ? 'bg-success-subtle border-success text-success-emphasis fw-semibold' : 'bg-white border-light-subtle text-muted';
                            $html .= "      <div class='px-3 py-2 rounded-3 border font-sans d-flex align-items-center gap-1.5 " . $bgClass . "' style='font-size: 0.95rem; cursor: default;'>";
                            $html .= "        <i data-lucide='grip-horizontal' style='width: 14px; height: 14px;'></i>";
                            $html .= "        <span>" . htmlspecialchars($opt) . "</span>";
                            $html .= "      </div>";
                        }
                    }
                    $html .= "      </div>";
                    $html .= "    </div>";
                }
                break;

            case 'drag_drop_ordered':
                $items = $qData['items'] ?? [];
                $correctOrder = $qData['correct_order'] ?? [];
                $distractors = $qData['distractors'] ?? [];

                $orderedItemsMap = [];
                foreach ($items as $item) {
                    $orderedItemsMap[$item['id']] = $item['text'];
                }

                $html .= "    <div class='mb-4'>";
                $html .= "      <div class='text-muted small uppercase mb-2 font-sans fw-semibold' style='font-size: 0.75rem; letter-spacing: 0.5px;'>Correct Sequence (In Order):</div>";
                $html .= "      <ol class='list-group list-group-numbered font-sans mb-3'>";
                foreach ($correctOrder as $itemId) {
                    $itemText = $orderedItemsMap[$itemId] ?? '(Unknown Item)';
                    $html .= "    <li class='list-group-item d-flex align-items-center gap-2 border-success bg-success-subtle text-success-emphasis py-2.5 px-3 rounded-3 mb-2'>";
                    $html .= "      <span class='fw-medium'>" . htmlspecialchars($itemText) . "</span>";
                    $html .= "    </li>";
                }
                $html .= "      </ol>";
                $html .= "    </div>";

                if (!empty($distractors)) {
                    $html .= "    <div class='mt-3'>";
                    $html .= "      <div class='text-muted small uppercase mb-2 font-sans fw-semibold' style='font-size: 0.75rem; letter-spacing: 0.5px;'>Distractors (Optional Extra Items):</div>";
                    $html .= "      <div class='d-flex flex-wrap gap-2'>";
                    foreach ($distractors as $dist) {
                        $html .= "    <div class='px-3 py-2 bg-light border text-muted rounded-3 font-sans d-flex align-items-center gap-1.5' style='font-size: 0.9rem;'>";
                        $html .= "      <i data-lucide='ban' class='text-danger' style='width: 14px; height: 14px;'></i>";
                        $html .= "      <span>" . htmlspecialchars($dist['text'] ?? '') . "</span>";
                        $html .= "    </div>";
                    }
                    $html .= "      </div>";
                    $html .= "    </div>";
                }
                break;

            case 'highlight':
                $passageHtml = $qData['passage_html'] ?? '';
                $segments = $qData['segments'] ?? [];
                $correctSegmentIds = $qData['correct_segment_ids'] ?? [];

                $sanitizedPassage = strip_tags($passageHtml, '<em><strong><br>');

                usort($segments, function($a, $b) {
                    return strlen($b['text'] ?? '') - strlen($a['text'] ?? '');
                });

                $placeholders = [];
                foreach ($segments as $seg) {
                    $segId = $seg['id'] ?? '';
                    $segText = $seg['text'] ?? '';
                    if ($segText === '') continue;

                    $isCorrect = in_array($segId, $correctSegmentIds);
                    $badgeClass = $isCorrect 
                        ? 'bg-success-subtle text-success-emphasis border-success fw-semibold' 
                        : 'bg-secondary-subtle text-muted border-light-subtle';
                    
                    $icon = $isCorrect ? '<i data-lucide="check" class="text-success d-inline" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 2px;"></i>' : '';

                    $wrappedHtml = "<span class='px-2 py-1 rounded-2 border d-inline-block " . $badgeClass . "' style='font-size: 0.95em; cursor: default; margin: 1px 0;'>$icon" . htmlspecialchars($segText) . "</span>";

                    $placeholder = "##SEGMENT_" . $segId . "##";
                    $placeholders[$placeholder] = $wrappedHtml;

                    $sanitizedPassage = str_replace($segText, $placeholder, $sanitizedPassage);
                }

                foreach ($placeholders as $placeholder => $wrappedHtml) {
                    $sanitizedPassage = str_replace($placeholder, $wrappedHtml, $sanitizedPassage);
                }

                $html .= "    <div class='p-4 bg-light rounded-3 font-sans border text-dark' style='line-height: 2.2; font-size: 1.1rem;'>";
                $html .= $sanitizedPassage;
                $html .= "    </div>";
                $html .= "    <div class='mt-3 d-flex gap-4 text-muted small uppercase font-sans fw-semibold' style='font-size: 0.75rem; letter-spacing: 0.5px;'>";
                $html .= "      <div class='d-flex align-items-center gap-1.5'><span class='d-inline-block' style='width: 12px; height: 12px; background-color: var(--bs-success-bg-subtle, #d1e7dd); border: 1px solid #198754; border-radius: 4px;'></span> Correct Segment</div>";
                $html .= "      <div class='d-flex align-items-center gap-1.5'><span class='d-inline-block' style='width: 12px; height: 12px; background-color: var(--bs-secondary-bg-subtle, #e2e3e5); border: 1px solid #adb5bd; border-radius: 4px;'></span> Incorrect/Neutral Segment</div>";
                $html .= "    </div>";
                break;

            case 'bowtie':
                $leftOptions = $qData['left_options'] ?? [];
                $centerOptions = $qData['center_options'] ?? [];
                $rightOptions = $qData['right_options'] ?? [];
                
                $leftTarget = $qData['left_target_count'] ?? 1;
                $centerTarget = $qData['center_target_count'] ?? 1;
                $rightTarget = $qData['right_target_count'] ?? 1;
                
                $correct = $qData['correct'] ?? [];
                $correctLeft = $correct['left'] ?? [];
                $correctCenter = $correct['center'] ?? [];
                $correctRight = $correct['right'] ?? [];

                $html .= "    <div class='row g-3 font-sans'>";
                
                // LEFT COLUMN
                $html .= "      <div class='col-md-4'>";
                $html .= "        <div class='p-3 border rounded-3 bg-light h-100 d-flex flex-column'>";
                $html .= "          <div class='d-flex justify-content-between align-items-center mb-2'>";
                $html .= "            <h6 class='fw-bold mb-0 text-dark'>Actions to Take</h6>";
                $html .= "            <span class='badge bg-secondary-subtle text-secondary border px-2.5 py-1' style='font-size: 0.75rem;'>Select " . htmlspecialchars($leftTarget) . "</span>";
                $html .= "          </div>";
                $html .= "          <hr class='my-2 opacity-50'>";
                $html .= "          <div class='d-flex flex-column gap-2 mt-1'>";
                foreach ($leftOptions as $opt) {
                    $optId = $opt['id'] ?? '';
                    $optText = $opt['text'] ?? '';
                    $isCorrect = in_array($optId, $correctLeft);
                    
                    if ($isCorrect) {
                        $html .= "        <div class='p-2.5 border border-success bg-success-subtle text-success-emphasis rounded-2 d-flex align-items-start gap-2' style='font-size: 0.9rem;'>";
                        $html .= "          <i data-lucide='check-circle-2' class='text-success mt-0.5 flex-shrink-0' style='width: 15px; height: 15px;'></i>";
                        $html .= "          <span class='fw-medium'>" . htmlspecialchars($optText) . "</span>";
                        $html .= "        </div>";
                    } else {
                        $html .= "        <div class='p-2.5 border bg-white text-muted rounded-2 d-flex align-items-start gap-2' style='font-size: 0.9rem; border-style: dashed;'>";
                        $html .= "          <span style='width: 15px;' class='flex-shrink-0'></span>";
                        $html .= "          <span>" . htmlspecialchars($optText) . "</span>";
                        $html .= "        </div>";
                    }
                }
                $html .= "          </div>";
                $html .= "        </div>";
                $html .= "      </div>";

                // CENTER COLUMN
                $html .= "      <div class='col-md-4'>";
                $html .= "        <div class='p-3 border border-primary border-opacity-25 rounded-3 bg-light h-100 d-flex flex-column' style='background-color: #f8fafc;'>";
                $html .= "          <div class='d-flex justify-content-between align-items-center mb-2'>";
                $html .= "            <h6 class='fw-bold mb-0 text-primary'>Condition Most Likely</h6>";
                $html .= "            <span class='badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1' style='font-size: 0.75rem;'>Select " . htmlspecialchars($centerTarget) . "</span>";
                $html .= "          </div>";
                $html .= "          <hr class='my-2 opacity-50'>";
                $html .= "          <div class='d-flex flex-column gap-2 mt-1'>";
                foreach ($centerOptions as $opt) {
                    $optId = $opt['id'] ?? '';
                    $optText = $opt['text'] ?? '';
                    $isCorrect = in_array($optId, $correctCenter);
                    
                    if ($isCorrect) {
                        $html .= "        <div class='p-2.5 border border-success bg-success-subtle text-success-emphasis rounded-2 d-flex align-items-start gap-2' style='font-size: 0.9rem;'>";
                        $html .= "          <i data-lucide='check-circle-2' class='text-success mt-0.5 flex-shrink-0' style='width: 15px; height: 15px;'></i>";
                        $html .= "          <span class='fw-medium'>" . htmlspecialchars($optText) . "</span>";
                        $html .= "        </div>";
                    } else {
                        $html .= "        <div class='p-2.5 border bg-white text-muted rounded-2 d-flex align-items-start gap-2' style='font-size: 0.9rem; border-style: dashed;'>";
                        $html .= "          <span style='width: 15px;' class='flex-shrink-0'></span>";
                        $html .= "          <span>" . htmlspecialchars($optText) . "</span>";
                        $html .= "        </div>";
                    }
                }
                $html .= "          </div>";
                $html .= "        </div>";
                $html .= "      </div>";

                // RIGHT COLUMN
                $html .= "      <div class='col-md-4'>";
                $html .= "        <div class='p-3 border rounded-3 bg-light h-100 d-flex flex-column'>";
                $html .= "          <div class='d-flex justify-content-between align-items-center mb-2'>";
                $html .= "            <h6 class='fw-bold mb-0 text-dark'>Parameters to Monitor</h6>";
                $html .= "            <span class='badge bg-secondary-subtle text-secondary border px-2.5 py-1' style='font-size: 0.75rem;'>Select " . htmlspecialchars($rightTarget) . "</span>";
                $html .= "          </div>";
                $html .= "          <hr class='my-2 opacity-50'>";
                $html .= "          <div class='d-flex flex-column gap-2 mt-1'>";
                foreach ($rightOptions as $opt) {
                    $optId = $opt['id'] ?? '';
                    $optText = $opt['text'] ?? '';
                    $isCorrect = in_array($optId, $correctRight);
                    
                    if ($isCorrect) {
                        $html .= "        <div class='p-2.5 border border-success bg-success-subtle text-success-emphasis rounded-2 d-flex align-items-start gap-2' style='font-size: 0.9rem;'>";
                        $html .= "          <i data-lucide='check-circle-2' class='text-success mt-0.5 flex-shrink-0' style='width: 15px; height: 15px;'></i>";
                        $html .= "          <span class='fw-medium'>" . htmlspecialchars($optText) . "</span>";
                        $html .= "        </div>";
                    } else {
                        $html .= "        <div class='p-2.5 border bg-white text-muted rounded-2 d-flex align-items-start gap-2' style='font-size: 0.9rem; border-style: dashed;'>";
                        $html .= "          <span style='width: 15px;' class='flex-shrink-0'></span>";
                        $html .= "          <span>" . htmlspecialchars($optText) . "</span>";
                        $html .= "        </div>";
                    }
                }
                $html .= "          </div>";
                $html .= "        </div>";
                $html .= "      </div>";

                $html .= "    </div>";
                break;

            case 'mcq_extended':
                $opts = $qData['options'] ?? [];
                $selectCount = $qData['select_count'] ?? 1;
                $html .= "    <div class='alert bg-primary-subtle text-primary border border-primary-subtle rounded-3 d-flex align-items-center gap-2 mb-3 py-2 px-3 font-sans'>";
                $html .= "      <i data-lucide='info' size='16'></i>";
                $html .= "      <span class='small fw-semibold'>Rule: Must select exactly " . htmlspecialchars($selectCount) . " options</span>";
                $html .= "    </div>";
                $html .= "    <div class='d-flex flex-column gap-2'>";
                foreach ($opts as $idx => $opt) {
                    $isCorrect = !empty($opt['is_correct']);
                    $bgClass = $isCorrect ? 'bg-success-subtle border-success-subtle text-success-emphasis' : 'bg-light border-light-subtle text-dark';
                    $icon = $isCorrect ? '<i data-lucide="check-circle" class="text-success" size="18"></i>' : '<i data-lucide="circle" class="text-muted" size="18"></i>';
                    
                    $html .= "      <div class='p-3 rounded-3 border d-flex align-items-center justify-content-between gap-3 " . $bgClass . "'>";
                    $html .= "        <div class='d-flex align-items-center gap-3'>";
                    $html .= "          <input class='form-check-input mt-0' type='checkbox' disabled " . ($isCorrect ? 'checked' : '') . " style='pointer-events: none;'>";
                    $html .= "          <span class='font-sans'>" . htmlspecialchars($opt['text'] ?? '') . "</span>";
                    $html .= "        </div>";
                    $html .= "        <div>" . $icon . "</div>";
                    $html .= "      </div>";
                }
                $html .= "    </div>";
                break;

            case 'fill_blank_calc':
                $correctValue = $qData['correct_value'] ?? '';
                $tolerance = $qData['tolerance'] ?? 0;
                $unit = $qData['unit'] ?? '';
                
                $html .= "    <div class='p-4 border rounded-3 bg-light font-sans d-flex flex-column gap-3' style='max-width: 500px;'>";
                $html .= "      <div class='d-flex align-items-center gap-2 mb-1'>";
                $html .= "        <i data-lucide='calculator' class='text-primary' size='20'></i>";
                $html .= "        <h6 class='fw-bold mb-0 text-dark'>Calculation Answer Key</h6>";
                $html .= "      </div>";
                $html .= "      <div class='row g-3'>";
                $html .= "        <div class='col-sm-6'>";
                $html .= "          <label class='form-label small text-muted fw-semibold mb-1'>Correct Value</label>";
                $html .= "          <div class='fs-5 fw-bold text-success'>" . htmlspecialchars($correctValue) . (!empty($unit) ? " " . htmlspecialchars($unit) : "") . "</div>";
                $html .= "        </div>";
                $html .= "        <div class='col-sm-6'>";
                $html .= "          <label class='form-label small text-muted fw-semibold mb-1'>Tolerance Range</label>";
                $html .= "          <div class='fs-6 text-dark fw-medium'>&plusmn; " . htmlspecialchars($tolerance) . "</div>";
                $html .= "          <span class='text-muted small'>[" . (floatval($correctValue) - floatval($tolerance)) . " &mdash; " . (floatval($correctValue) + floatval($tolerance)) . "]</span>";
                $html .= "        </div>";
                $html .= "      </div>";
                $html .= "      <div class='mt-2 pt-3 border-top'>";
                $html .= "        <label class='form-label small text-muted fw-semibold mb-1'>Student Input Mockup</label>";
                $html .= "        <div class='input-group' style='max-width: 250px;'>";
                $html .= "          <input type='text' class='form-control bg-white' placeholder='Enter value...' disabled style='pointer-events: none;'>";
                if (!empty($unit)) {
                    $html .= "      <span class='input-group-text bg-light text-muted fw-medium'>" . htmlspecialchars($unit) . "</span>";
                }
                $html .= "        </div>";
                $html .= "      </div>";
                $html .= "    </div>";
                break;

            case 'essay':
                $html .= "    <div class='p-3 border rounded-3 bg-light font-sans d-flex align-items-start gap-2'>";
                $html .= "      <i data-lucide='file-text' class='text-warning mt-0.5' size='18'></i>";
                $html .= "      <div>";
                $html .= "        <span class='fw-semibold text-dark d-block mb-1'>Essay Response Required</span>";
                $html .= "        <span class='text-muted small'>This is a free-text response. There is no predefined correct answer key. Instructors will grade student submissions manually from the grading queue.</span>";
                $html .= "      </div>";
                $html .= "    </div>";
                break;

            default:
                $html .= "    <div class='alert alert-secondary border-0 rounded-3 mb-0'>Unsupported question type.</div>";
                break;
        }

        $html .= "    </div>";
        $html .= "  </div>";
        $html .= "</div>";

        return $html;
    }

    /**
     * Legacy/Student taking interactive rendering
     */
    private static function renderStudentTaking($question, $options, $userAnswer = null, $disabled = false) {
        $type = $question['type'];
        $html = "<div class='card mb-4 shadow-sm border-0 rounded-3 id-question-card-" . $question['id'] . "' id='question-card-" . $question['id'] . "'>";
        $html .= "  <div class='card-header bg-light d-flex justify-content-between align-items-center py-3 border-0'>";
        $html .= "    <h5 class='mb-0 text-secondary d-flex align-items-center gap-2'>";
        $html .= "      <span class='badge bg-primary rounded-pill px-3'>" . self::getTypeLabel($type) . "</span>";
        $html .= "      <span class='text-muted fs-6'>(" . floatval($question['points']) . " pts)</span>";
        $html .= "    </h5>";
        $difficultyClass = self::getDifficultyBadgeClass($question['difficulty']);
        $html .= "    <span class='badge " . $difficultyClass . "'>" . ucfirst($question['difficulty']) . "</span>";
        $html .= "  </div>";
        $html .= "  <div class='card-body p-4'>";
        $html .= "    <div class='fs-5 mb-4 text-dark font-sans' style='white-space: pre-line;'>" . htmlspecialchars($question['question_text']) . "</div>";
        $html .= "    <div class='question-options-container'>";
        
        $disableAttr = $disabled ? "disabled" : "";

        // Standardize mcq_multi_sata to render exactly like old mcq_multi
        $effectiveType = ($type === 'mcq_multi_sata') ? 'mcq_multi' : $type;

        switch ($effectiveType) {
            case 'mcq_single':
                foreach ($options as $opt) {
                    $checked = '';
                    if ($userAnswer !== null) {
                        if (is_array($userAnswer) && in_array($opt['id'], $userAnswer)) {
                            $checked = 'checked';
                        } else if ($userAnswer == $opt['id']) {
                            $checked = 'checked';
                        }
                    }
                    $html .= "
                    <div class='form-check option-item p-3 mb-2 rounded border border-light-subtle hover-bg-light transition-all d-flex align-items-center'>
                        <input class='form-check-input ms-0 me-3' type='radio' name='q[" . $question['id'] . "]' id='opt_" . $opt['id'] . "' value='" . $opt['id'] . "' " . $checked . " " . $disableAttr . " onchange='saveAnswer(" . $question['id'] . ")'>
                        <label class='form-check-label w-full cursor-pointer mb-0' for='opt_" . $opt['id'] . "'>" . htmlspecialchars($opt['option_text']) . "</label>
                    </div>";
                }
                break;
                
            case 'mcq_multi':
                foreach ($options as $opt) {
                    $checked = '';
                    if (is_array($userAnswer) && in_array($opt['id'], $userAnswer)) {
                        $checked = 'checked';
                    }
                    $html .= "
                    <div class='form-check option-item p-3 mb-2 rounded border border-light-subtle hover-bg-light transition-all d-flex align-items-center'>
                        <input class='form-check-input ms-0 me-3' type='checkbox' name='q[" . $question['id'] . "][]' id='opt_" . $opt['id'] . "' value='" . $opt['id'] . "' " . $checked . " " . $disableAttr . " onchange='saveAnswer(" . $question['id'] . ")'>
                        <label class='form-check-label w-full cursor-pointer mb-0' for='opt_" . $opt['id'] . "'>" . htmlspecialchars($opt['option_text']) . "</label>
                    </div>";
                }
                break;
                
            case 'true_false':
                $trueChecked = ($userAnswer === '1' || $userAnswer === 1 || $userAnswer === true || $userAnswer === 'true') ? 'checked' : '';
                $falseChecked = ($userAnswer === '0' || $userAnswer === 0 || $userAnswer === false || $userAnswer === 'false') ? 'checked' : '';
                
                $html .= "
                <div class='form-check option-item p-3 mb-2 rounded border border-light-subtle hover-bg-light transition-all d-flex align-items-center'>
                    <input class='form-check-input ms-0 me-3' type='radio' name='q[" . $question['id'] . "]' id='tf_true_" . $question['id'] . "' value='true' " . $trueChecked . " " . $disableAttr . " onchange='saveAnswer(" . $question['id'] . ")'>
                    <label class='form-check-label w-full cursor-pointer mb-0' for='tf_true_" . $question['id'] . "'>True</label>
                </div>
                <div class='form-check option-item p-3 mb-2 rounded border border-light-subtle hover-bg-light transition-all d-flex align-items-center'>
                    <input class='form-check-input ms-0 me-3' type='radio' name='q[" . $question['id'] . "]' id='tf_false_" . $question['id'] . "' value='false' " . $falseChecked . " " . $disableAttr . " onchange='saveAnswer(" . $question['id'] . ")'>
                    <label class='form-check-label w-full cursor-pointer mb-0' for='tf_false_" . $question['id'] . "'>False</label>
                </div>";
                break;
                
            case 'fill_blank':
                $textValue = is_string($userAnswer) ? htmlspecialchars($userAnswer) : '';
                $html .= "
                <div class='mb-3'>
                    <input type='text' class='form-control form-control-lg' name='q[" . $question['id'] . "]' placeholder='Type your answer here...' value='" . $textValue . "' " . $disableAttr . " onblur='saveAnswer(" . $question['id'] . ")' oninput='debouncedSave(" . $question['id'] . ")'>
                </div>";
                break;
                
            case 'matching':
                $leftItems = $options;
                $rightItems = array_map(function($o) { return $o['pair_key']; }, $options);
                $rightItems = array_unique(array_filter($rightItems));
                shuffle($rightItems);
                
                $html .= "<div class='table-responsive'><table class='table table-bordered align-middle'>";
                $html .= "  <thead><tr class='bg-light'><th>Concept</th><th>Matching Term</th></tr></thead>";
                $html .= "  <tbody>";
                
                foreach ($leftItems as $idx => $item) {
                    $selectedPair = '';
                    if (is_array($userAnswer) && isset($userAnswer[$item['id']])) {
                        $selectedPair = $userAnswer[$item['id']];
                    }
                    
                    $html .= "<tr>";
                    $html .= "  <td class='p-3' style='width: 50%;'>" . htmlspecialchars($item['option_text']) . "</td>";
                    $html .= "  <td class='p-3'>";
                    $html .= "    <select class='form-select form-select-md matching-select' name='q[" . $question['id'] . "][" . $item['id'] . "]' " . $disableAttr . " onchange='saveAnswer(" . $question['id'] . ")'>";
                    $html .= "      <option value=''>-- Select Match --</option>";
                    foreach ($rightItems as $right) {
                        $sel = ($selectedPair == $right) ? 'selected' : '';
                        $html .= "    <option value='" . htmlspecialchars($right) . "' " . $sel . ">" . htmlspecialchars($right) . "</option>";
                    }
                    $html .= "    </select>";
                    $html .= "  </td>";
                    $html .= "</tr>";
                }
                
                $html .= "  </tbody>";
                $html .= "</table></div>";
                break;
                
            case 'essay':
                $essayText = is_string($userAnswer) ? htmlspecialchars($userAnswer) : '';
                $html .= "
                <div class='mb-3'>
                    <textarea class='form-control font-sans text-dark' name='q[" . $question['id'] . "]' rows='6' placeholder='Type your detailed essay response here...' " . $disableAttr . " onblur='saveAnswer(" . $question['id'] . ")' oninput='debouncedSave(" . $question['id'] . ")'>" . $essayText . "</textarea>
                </div>";
                break;
        }
        
        $html .= "    </div>";
        $html .= "  </div>";
        $html .= "</div>";
        return $html;
    }
    
    /**
     * Renders a question in review mode, showing correct answers, scoring, and explanation.
     */
    public static function renderReview($question, $options, $userAnswer, $isCorrect, $pointsAwarded, $needsManual) {
        $type = $question['type'];
        $cardBorder = $needsManual ? "border-warning" : ($isCorrect ? "border-success" : "border-danger");
        $headerBg = $needsManual ? "bg-warning-subtle" : ($isCorrect ? "bg-success-subtle" : "bg-danger-subtle");
        $textClass = $needsManual ? "text-warning-emphasis" : ($isCorrect ? "text-success-emphasis" : "text-danger-emphasis");
        
        $html = "<div class='card mb-4 shadow-sm border " . $cardBorder . " rounded-3' id='review-q-" . $question['id'] . "'>";
        $html .= "  <div class='card-header " . $headerBg . " " . $textClass . " d-flex justify-content-between align-items-center py-3'>";
        $html .= "    <h5 class='mb-0 font-sans d-flex align-items-center gap-2'>";
        $html .= "      <span class='badge bg-dark rounded-pill px-3'>" . self::getTypeLabel($type) . "</span>";
        $html .= "      <span>" . htmlspecialchars($question['question_text']) . "</span>";
        $html .= "    </h5>";
        $html .= "    <span class='fw-bold fs-5'>";
        if ($needsManual) {
            $html .= "      <span class='badge bg-warning text-dark'>Needs Manual Grading</span>";
        } else {
            $html .= "      " . floatval($pointsAwarded) . " / " . floatval($question['points']) . " pts";
        }
        $html .= "    </span>";
        $html .= "  </div>";
        $html .= "  <div class='card-body p-4 bg-white'>";
        
        if (!$needsManual) {
            if ($isCorrect) {
                $html .= "<div class='alert alert-success d-flex align-items-center gap-2 mb-4 py-2 border-0 rounded-3'><i class='lucide-check-circle-2'></i> <span>Correct Answer!</span></div>";
            } else {
                $html .= "<div class='alert alert-danger d-flex align-items-center gap-2 mb-4 py-2 border-0 rounded-3'><i class='lucide-x-circle'></i> <span>Incorrect. Review the correct options below.</span></div>";
            }
        }
        
        $html .= "    <div class='question-options-container'>";
        
        $effectiveType = ($type === 'mcq_multi_sata') ? 'mcq_multi' : $type;

        switch ($effectiveType) {
            case 'mcq_single':
                foreach ($options as $opt) {
                    $checked = ($userAnswer == $opt['id'] || (is_array($userAnswer) && in_array($opt['id'], $userAnswer))) ? 'checked' : '';
                    $correctIcon = $opt['is_correct'] ? "<span class='badge bg-success ms-2'><i class='lucide-check'></i> Correct</span>" : "";
                    $bgClass = $opt['is_correct'] ? "bg-success-subtle border-success-subtle" : ($checked ? "bg-danger-subtle border-danger-subtle" : "border-light-subtle");
                    
                    $html .= "
                    <div class='option-item p-3 mb-2 rounded border " . $bgClass . " d-flex align-items-center justify-content-between'>
                        <div class='form-check mb-0'>
                            <input class='form-check-input ms-0 me-3' type='radio' disabled " . $checked . ">
                            <label class='form-check-label w-full cursor-default mb-0'>" . htmlspecialchars($opt['option_text']) . "</label>
                        </div>
                        " . $correctIcon . "
                    </div>";
                }
                break;
                
            case 'mcq_multi':
                foreach ($options as $opt) {
                    $checked = (is_array($userAnswer) && in_array($opt['id'], $userAnswer)) ? 'checked' : '';
                    $correctIcon = $opt['is_correct'] ? "<span class='badge bg-success ms-2'><i class='lucide-check'></i> Correct</span>" : "";
                    $bgClass = $opt['is_correct'] ? "bg-success-subtle border-success-subtle" : ($checked ? "bg-danger-subtle border-danger-subtle" : "border-light-subtle");
                    
                    $html .= "
                    <div class='option-item p-3 mb-2 rounded border " . $bgClass . " d-flex align-items-center justify-content-between'>
                        <div class='form-check mb-0'>
                            <input class='form-check-input ms-0 me-3' type='checkbox' disabled " . $checked . ">
                            <label class='form-check-label w-full cursor-default mb-0'>" . htmlspecialchars($opt['option_text']) . "</label>
                        </div>
                        " . $correctIcon . "
                    </div>";
                }
                break;
                
            case 'true_false':
                $trueChecked = ($userAnswer === 'true' || $userAnswer === true || $userAnswer === '1' || $userAnswer === 1) ? 'checked' : '';
                $falseChecked = ($userAnswer === 'false' || $userAnswer === false || $userAnswer === '0' || $userAnswer === 0) ? 'checked' : '';
                
                $correctVal = 'false';
                foreach ($options as $opt) {
                    if ($opt['is_correct']) {
                        $correctVal = strtolower($opt['option_text']);
                    }
                }
                
                $trueCorrect = ($correctVal === 'true' || $correctVal === '1');
                $falseCorrect = ($correctVal === 'false' || $correctVal === '0');
                
                $html .= "
                <div class='option-item p-3 mb-2 rounded border " . ($trueCorrect ? 'bg-success-subtle border-success-subtle' : ($trueChecked ? 'bg-danger-subtle border-danger-subtle' : 'border-light-subtle')) . " d-flex align-items-center justify-content-between'>
                    <div class='form-check mb-0'>
                        <input class='form-check-input ms-0 me-3' type='radio' disabled " . $trueChecked . ">
                        <label class='form-check-label mb-0'>True</label>
                    </div>
                    " . ($trueCorrect ? "<span class='badge bg-success'><i class='lucide-check'></i> Correct Answer</span>" : "") . "
                </div>
                <div class='option-item p-3 mb-2 rounded border " . ($falseCorrect ? 'bg-success-subtle border-success-subtle' : ($falseChecked ? 'bg-danger-subtle border-danger-subtle' : 'border-light-subtle')) . " d-flex align-items-center justify-content-between'>
                    <div class='form-check mb-0'>
                        <input class='form-check-input ms-0 me-3' type='radio' disabled " . $falseChecked . ">
                        <label class='form-check-label mb-0'>False</label>
                    </div>
                    " . ($falseCorrect ? "<span class='badge bg-success'><i class='lucide-check'></i> Correct Answer</span>" : "") . "
                </div>";
                break;
                
            case 'fill_blank':
                $html .= "
                <div class='mb-3'>
                    <label class='form-label text-muted'>Your Answer:</label>
                    <input type='text' class='form-control border " . ($isCorrect ? "border-success" : "border-danger") . "' disabled value='" . htmlspecialchars($userAnswer ?? '') . "'>
                </div>
                <div class='mt-2 p-3 bg-light rounded-3'>
                    <strong>Accepted Correct Answers:</strong>
                    <ul class='mb-0 mt-1 pl-3'>";
                foreach ($options as $opt) {
                    $html .= "<li>" . htmlspecialchars($opt['option_text']) . "</li>";
                }
                $html .= "</ul></div>";
                break;
                
            case 'matching':
                $html .= "<div class='table-responsive'><table class='table table-bordered align-middle'>";
                $html .= "  <thead><tr class='bg-light'><th>Concept</th><th>Your Match</th><th>Correct Match</th><th>Status</th></tr></thead>";
                $html .= "  <tbody>";
                
                foreach ($options as $item) {
                    $userMatch = isset($userAnswer[$item['id']]) ? $userAnswer[$item['id']] : '';
                    $correctMatch = $item['pair_key'];
                    $matchCorrect = (strtolower(trim($userMatch)) === strtolower(trim($correctMatch)));
                    
                    $html .= "<tr>";
                    $html .= "  <td class='p-3'>" . htmlspecialchars($item['option_text']) . "</td>";
                    $html .= "  <td class='p-3 " . ($matchCorrect ? 'text-success fw-medium' : 'text-danger') . "'>" . htmlspecialchars($userMatch ?: '(Unanswered)') . "</td>";
                    $html .= "  <td class='p-3 text-success fw-medium'>" . htmlspecialchars($correctMatch) . "</td>";
                    $html .= "  <td class='p-3 text-center'>";
                    $html .= $matchCorrect ? "<span class='badge bg-success'><i class='lucide-check'></i></span>" : "<span class='badge bg-danger'><i class='lucide-x'></i></span>";
                    $html .= "  </td>";
                    $html .= "</tr>";
                }
                
                $html .= "  </tbody>";
                $html .= "</table></div>";
                break;
                
            case 'essay':
                $html .= "
                <div class='mb-3'>
                    <label class='form-label text-muted'>Your Written Response:</label>
                    <div class='p-3 border rounded bg-light-subtle' style='white-space: pre-wrap; font-family: sans-serif;'>" . htmlspecialchars($userAnswer ?? '') . "</div>
                </div>";
                
                if ($needsManual) {
                    $html .= "<div class='alert alert-warning border-0 rounded-3 py-2'><i class='lucide-info me-2'></i> Waiting for an instructor to review and grade this question.</div>";
                } else {
                    $html .= "<div class='alert alert-success border-0 rounded-3 py-2'><i class='lucide-check-circle me-2'></i> Graded: " . floatval($pointsAwarded) . " / " . floatval($question['points']) . " points awarded.</div>";
                }
                break;
        }
        
        $html .= "    </div>";
        $html .= "  </div>";
        $html .= "</div>";
        return $html;
    }

    public static function getTypeLabel($type) {
        $labels = [
            'mcq_single' => 'Multiple Choice (Single)',
            'mcq_multi_sata' => 'Multiple Choice (SATA)',
            'mcq_multi' => 'Multiple Choice (Multiple)',
            'true_false' => 'True/False',
            'fill_blank' => 'Fill in the Blank',
            'matching' => 'Matching',
            'essay' => 'Essay',
            'mcq_extended' => 'Extended Multiple Choice',
            'cloze_dropdown' => 'Cloze Dropdown',
            'cloze_dragdrop' => 'Cloze Drag and Drop',
            'drag_drop_ordered' => 'Drag and Drop Ordered',
            'matrix_single' => 'Matrix Single Select',
            'matrix_multi' => 'Matrix Multiple Select',
            'highlight' => 'Highlight Select',
            'bowtie' => 'Bowtie Scenario',
            'fill_blank_calc' => 'Calculated Fill in the Blank'
        ];
        return $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    private static function getDifficultyBadgeClass($difficulty) {
        $classes = [
            'easy' => 'bg-success text-white',
            'medium' => 'bg-warning text-dark',
            'hard' => 'bg-danger text-white'
        ];
        return $classes[$difficulty] ?? 'bg-secondary text-white';
    }

    /**
     * Renders student-facing interactive question interface for all 14 types
     */
    public static function renderInteractive($question, $existingAnswer = null) {
        $type = $question['type'];
        $qId = intval($question['id']);
        
        // Parse metadata/question_data
        $qData = [];
        if (is_string($question['question_data'] ?? '')) {
            $qData = json_decode($question['question_data'], true) ?: [];
        } else if (is_array($question['question_data'] ?? null)) {
            $qData = $question['question_data'];
        }

        // Parse saved answer
        $ansData = null;
        if ($existingAnswer !== null) {
            if (is_array($existingAnswer)) {
                $ansData = $existingAnswer['answer_data'] ?? null;
            } else {
                $ansData = $existingAnswer;
            }
        }
        if (is_string($ansData)) {
            $ansData = json_decode($ansData, true);
        }

        $options = $qData['options'] ?? [];

        // Build HTML wrapper
        $html = "<div class='card border-0 shadow-sm rounded-3 overflow-hidden mb-3 interactive-question-card' id='interactive-q-{$qId}' data-question-id='{$qId}' data-type='{$type}'>";
        
        // Header
        $html .= "  <div class='card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2'>";
        $html .= "    <div class='d-flex align-items-center gap-2'>";
        $html .= "      <span class='badge bg-primary text-white font-sans px-2.5 py-1.5' style='font-size: 0.75rem;'>" . self::getTypeLabel($type) . "</span>";
        $html .= "      <span class='text-muted small font-sans'>(" . floatval($question['points'] ?? 1.00) . " pts)</span>";
        $html .= "    </div>";
        $difficultyClass = self::getDifficultyBadgeClass($question['difficulty'] ?? 'medium');
        $html .= "    <span class='badge " . $difficultyClass . " font-sans text-capitalize'>" . htmlspecialchars($question['difficulty'] ?? 'medium') . "</span>";
        $html .= "  </div>";

        // Body
        $html .= "  <div class='card-body p-4'>";
        $html .= "    <div class='fs-5 fw-medium text-dark font-sans mb-4' style='white-space: pre-wrap;'>" . htmlspecialchars($question['question_text'] ?? '') . "</div>";
        $html .= "    <div class='question-interactive-content'>";

        switch ($type) {
            case 'mcq_single':
            case 'true_false':
                $selected = $ansData['selected'] ?? '';
                $html .= "<div class='mcq-single-container d-flex flex-column gap-2' data-question-id='{$qId}'>";
                foreach ($options as $opt) {
                    $checked = ($opt['id'] == $selected) ? 'checked' : '';
                    $html .= "
                    <label class='form-check option-item p-3 mb-1 rounded border border-light-subtle d-flex align-items-center gap-3 cursor-pointer hover-bg-light transition-all'>
                        <input class='form-check-input mt-0' type='radio' name='q_{$qId}' value='{$opt['id']}' {$checked} onchange='onMCQSingleChange({$qId}, this.value)' style='cursor: pointer;'>
                        <span class='font-sans'>".htmlspecialchars($opt['text'] ?? '')."</span>
                    </label>";
                }
                $html .= "</div>";
                break;

            case 'mcq_multi_sata':
            case 'mcq_multi':
            case 'mcq_extended':
                $selectedList = $ansData['selected'] ?? [];
                if (!is_array($selectedList)) { $selectedList = !empty($selectedList) ? [$selectedList] : []; }

                if ($type === 'mcq_extended') {
                    $selectCount = intval($qData['select_count'] ?? 1);
                    $html .= "<div class='mcq-extended-container' data-question-id='{$qId}' data-select-count='{$selectCount}'>";
                    $html .= "  <div class='alert alert-info py-2 px-3 small font-sans mb-3 d-flex justify-content-between align-items-center'>";
                    $html .= "    <span>Please select exactly <strong>{$selectCount}</strong> option(s).</span>";
                    $html .= "    <span class='extended-counter fw-bold'><span class='current-count'>".count($selectedList)."</span> of {$selectCount} selected</span>";
                    $html .= "  </div>";
                } else {
                    $html .= "<div class='mcq-multi-container d-flex flex-column gap-2' data-question-id='{$qId}'>";
                }

                foreach ($options as $opt) {
                    $checked = in_array($opt['id'], $selectedList) ? 'checked' : '';
                    $onChange = ($type === 'mcq_extended') ? "onMCQExtendedChange({$qId}, {$selectCount})" : "onMCQMultiChange({$qId})";
                    $html .= "
                    <label class='form-check option-item p-3 mb-1 rounded border border-light-subtle d-flex align-items-center gap-3 cursor-pointer hover-bg-light transition-all'>
                        <input class='form-check-input mt-0' type='checkbox' name='q_{$qId}[]' value='{$opt['id']}' {$checked} onchange='{$onChange}' style='cursor: pointer;'>
                        <span class='font-sans'>".htmlspecialchars($opt['text'] ?? '')."</span>
                    </label>";
                }
                $html .= "</div>";
                break;

            case 'matching':
                $left = $qData['left'] ?? [];
                $right = $qData['right'] ?? [];
                $pairs = $ansData['pairs'] ?? [];
                $pairMap = [];
                foreach ($pairs as $p) {
                    if (isset($p[0]) && isset($p[1])) {
                        $pairMap[$p[0]] = $p[1];
                    }
                }

                $html .= "<div class='matching-container' data-question-id='{$qId}'>";
                $html .= "  <div class='table-responsive border rounded-3'>";
                $html .= "    <table class='table table-bordered align-middle mb-0 font-sans'>";
                $html .= "      <thead class='table-light text-muted small uppercase'><tr><th style='width: 50%;'>Concept</th><th style='width: 50%;'>Your Match</th></tr></thead>";
                $html .= "      <tbody>";
                foreach ($left as $lItem) {
                    $selectedRight = $pairMap[$lItem['id']] ?? '';
                    $html .= "<tr>";
                    $html .= "  <td class='p-3 fw-medium text-dark'>" . htmlspecialchars($lItem['text'] ?? '') . "</td>";
                    $html .= "  <td class='p-3'>";
                    $html .= "    <select class='form-select border-primary' onchange='onMatchingChange({$qId})' data-left-id='".htmlspecialchars($lItem['id'])."'>";
                    $html .= "      <option value=''>-- Select Match --</option>";
                    foreach ($right as $rItem) {
                        $sel = ($rItem['id'] == $selectedRight) ? 'selected' : '';
                        $html .= "  <option value='".htmlspecialchars($rItem['id'])."' {$sel}>".htmlspecialchars($rItem['text'] ?? '')."</option>";
                    }
                    $html .= "    </select>";
                    $html .= "  </td>";
                    $html .= "</tr>";
                }
                $html .= "      </tbody>";
                $html .= "    </table>";
                $html .= "  </div>";
                $html .= "</div>";
                break;

            case 'drag_drop_ordered':
                $items = $qData['items'] ?? [];
                $distractors = $qData['distractors'] ?? [];
                $allPossible = array_merge($items, $distractors);

                $savedOrder = $ansData['order'] ?? [];
                if (!empty($savedOrder)) {
                    $ordered = [];
                    $byKey = [];
                    foreach ($allPossible as $itm) { $byKey[$itm['id']] = $itm; }
                    foreach ($savedOrder as $id) {
                        if (isset($byKey[$id])) {
                            $ordered[] = $byKey[$id];
                            unset($byKey[$id]);
                        }
                    }
                    foreach ($byKey as $itm) { $ordered[] = $itm; }
                    $allPossible = $ordered;
                } else {
                    shuffle($allPossible);
                }

                $html .= "<div class='drag-drop-ordered-container' data-question-id='{$qId}'>";
                $html .= "  <div class='alert alert-light border py-1.5 px-3 small font-sans text-muted mb-2 d-flex align-items-center gap-2'>";
                $html .= "    <i class='lucide-grip-vertical text-muted' style='width:16px; height:16px;'></i>";
                $html .= "    <span>Drag and drop items to arrange them in the correct order.</span>";
                $html .= "  </div>";
                $html .= "  <ul class='list-group sortable-list' id='sortable_{$qId}' style='cursor: grab;'>";
                foreach ($allPossible as $itm) {
                    $html .= "  <li class='list-group-item d-flex align-items-center gap-3 py-3 px-3 mb-2 border rounded-3 bg-white shadow-sm' data-id='".htmlspecialchars($itm['id'])."'>";
                    $html .= "    <i class='lucide-grip-vertical text-muted' style='width: 18px; height: 18px;'></i>";
                    $html .= "    <span class='font-sans fw-medium text-dark'>".htmlspecialchars($itm['text'] ?? '')."</span>";
                    $html .= "  </li>";
                }
                $html .= "  </ul>";
                $html .= "</div>";
                break;

            case 'matrix_single':
            case 'matrix_multi':
                $rows = $qData['rows'] ?? [];
                $columns = $qData['columns'] ?? [];
                $answers = $ansData['answers'] ?? [];

                $isMulti = ($type === 'matrix_multi');
                $inputType = $isMulti ? 'checkbox' : 'radio';

                $html .= "<div class='matrix-container' data-question-id='{$qId}' data-matrix-type='{$type}'>";
                $html .= "  <div class='table-responsive border rounded-3'>";
                $html .= "    <table class='table table-bordered align-middle text-center mb-0 font-sans'>";
                $html .= "      <thead class='table-light text-muted small uppercase'>";
                $html .= "        <tr>";
                $html .= "          <th class='text-start' style='width: 40%;'>Findings / Rows</th>";
                foreach ($columns as $col) {
                    $html .= "      <th>" . htmlspecialchars($col['label'] ?? '') . "</th>";
                }
                $html .= "        </tr>";
                $html .= "      </thead>";
                $html .= "      <tbody>";
                foreach ($rows as $row) {
                    $rowId = $row['id'] ?? '';
                    $selectedCols = $answers[$rowId] ?? [];
                    if (!is_array($selectedCols)) { $selectedCols = !empty($selectedCols) ? [$selectedCols] : []; }

                    $html .= "        <tr data-row-id='".htmlspecialchars($rowId)."'>";
                    $html .= "          <td class='text-start fw-medium text-dark'>" . htmlspecialchars($row['label'] ?? '') . "</td>";
                    foreach ($columns as $col) {
                        $colId = $col['id'] ?? '';
                        $checked = in_array($colId, $selectedCols) ? 'checked' : '';
                        $inputName = "matrix_{$qId}_" . htmlspecialchars($rowId);
                        $html .= "        <td>";
                        $html .= "          <input class='form-check-input' type='{$inputType}' name='{$inputName}" . ($isMulti ? '[]' : '') . "' value='".htmlspecialchars($colId)."' {$checked} onchange='onMatrixChange({$qId})' style='transform: scale(1.1); cursor: pointer;'>";
                        $html .= "        </td>";
                    }
                    $html .= "        </tr>";
                }
                $html .= "      </tbody>";
                $html .= "    </table>";
                $html .= "  </div>";
                $html .= "</div>";
                break;

            case 'cloze_dropdown':
                $passage = $qData['passage'] ?? '';
                $blanks = $qData['blanks'] ?? [];
                $savedBlanks = $ansData['blanks'] ?? [];

                $blanksMap = [];
                foreach ($blanks as $blank) {
                    $blanksMap[$blank['id']] = $blank;
                }

                $escapedPassage = htmlspecialchars($passage);
                $renderedPassage = preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($blanksMap, $savedBlanks, $qId) {
                    $blankId = trim($matches[1]);
                    if (!isset($blanksMap[$blankId])) {
                        return "{{ " . htmlspecialchars($blankId) . " }}";
                    }

                    $blank = $blanksMap[$blankId];
                    $options = $blank['options'] ?? [];
                    $selectedVal = $savedBlanks[$blankId] ?? '';

                    $selectHtml = "<select class='form-select form-select-sm d-inline-block w-auto border-primary' onchange='onClozeDropdownChange({$qId})' data-blank-id='".htmlspecialchars($blankId)."' style='margin: 0 4px;'>";
                    $selectHtml .= "<option value=''>-- Select --</option>";
                    foreach ($options as $opt) {
                        $sel = ($opt === $selectedVal) ? 'selected' : '';
                        $selectHtml .= "<option value='".htmlspecialchars($opt)."' {$sel}>" . htmlspecialchars($opt) . "</option>";
                    }
                    $selectHtml .= "</select>";
                    return $selectHtml;
                }, $escapedPassage);

                $html .= "<div class='cloze-dropdown-container' data-question-id='{$qId}'>";
                $html .= "  <div class='p-3 bg-light rounded-3 font-sans border' style='line-height: 1.8; font-size: 1.1rem; white-space: pre-wrap;'>";
                $html .= $renderedPassage;
                $html .= "  </div>";
                $html .= "</div>";
                break;

            case 'cloze_dragdrop':
                $passage = $qData['passage'] ?? '';
                $blanks = $qData['blanks'] ?? [];
                $savedBlanks = $ansData['blanks'] ?? [];

                $blanksMap = [];
                foreach ($blanks as $blank) {
                    $blanksMap[$blank['id']] = $blank;
                }

                $escapedPassage = htmlspecialchars($passage);
                $renderedPassage = preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($blanksMap, $savedBlanks, $qId) {
                    $blankId = trim($matches[1]);
                    if (!isset($blanksMap[$blankId])) {
                        return "{{ " . htmlspecialchars($blankId) . " }}";
                    }

                    $savedVal = $savedBlanks[$blankId] ?? '';
                    $displayVal = $savedVal !== '' ? htmlspecialchars($savedVal) : 'Drop here';
                    $activeClass = $savedVal !== '' ? 'bg-primary-subtle border-primary text-primary-emphasis fw-bold' : 'bg-white border-dashed text-muted';

                    return "<span class='cloze-drop-target d-inline-flex align-items-center justify-content-center border rounded px-3 py-1 align-middle {$activeClass}' data-blank-id='".htmlspecialchars($blankId)."' style='min-width: 120px; min-height: 36px; margin: 0 4px; cursor: pointer;' onclick='onClozeTargetClick({$qId}, this)'>{$displayVal}</span>";
                }, $escapedPassage);

                $poolOptions = [];
                foreach ($blanks as $b) {
                    foreach ($b['options'] ?? [] as $opt) {
                        $poolOptions[] = $opt;
                    }
                }
                $poolOptions = array_unique($poolOptions);
                shuffle($poolOptions);

                $html .= "<div class='cloze-dragdrop-container' data-question-id='{$qId}'>";
                $html .= "  <div class='p-3 bg-light rounded-3 font-sans border text-dark mb-3' style='line-height: 2.0; font-size: 1.1rem; white-space: pre-wrap;'>";
                $html .= $renderedPassage;
                $html .= "  </div>";
                $html .= "  <div class='option-pool-section'>";
                $html .= "    <div class='text-muted small uppercase mb-2 font-sans fw-semibold' style='font-size: 0.75rem; letter-spacing: 0.5px;'>Click an option below, then click a target slot above to place it:</div>";
                $html .= "    <div class='d-flex flex-wrap gap-2 align-items-center'>";
                foreach ($poolOptions as $opt) {
                    $html .= "    <button type='button' class='btn btn-outline-secondary btn-sm font-sans px-3 py-2 rounded-3 option-token d-flex align-items-center gap-1.5' onclick='onClozeOptionSelect({$qId}, this)' data-value='".htmlspecialchars($opt)."'>";
                    $html .= "      <i class='lucide-grip-horizontal' style='width: 14px; height: 14px;'></i>";
                    $html .= "      <span>" . htmlspecialchars($opt) . "</span>";
                    $html .= "    </button>";
                }
                $html .= "    <button type='button' class='btn btn-outline-danger btn-sm font-sans rounded-3' onclick='clearClozeSelections({$qId})'>Clear All</button>";
                $html .= "    </div>";
                $html .= "  </div>";
                $html .= "</div>";
                break;

            case 'highlight':
                $passageHtml = $qData['passage_html'] ?? '';
                $segments = $qData['segments'] ?? [];
                $selectedSegments = $ansData['segments'] ?? [];

                $sanitizedPassage = strip_tags($passageHtml, '<em><strong><br>');

                usort($segments, function($a, $b) {
                    return strlen($b['text'] ?? '') - strlen($a['text'] ?? '');
                });

                $placeholders = [];
                foreach ($segments as $seg) {
                    $segId = $seg['id'] ?? '';
                    $segText = $seg['text'] ?? '';
                    if ($segText === '') continue;

                    $isSelected = in_array($segId, $selectedSegments);
                    $activeClass = $isSelected ? 'bg-primary text-white border-primary fw-bold shadow-sm' : 'bg-light hover-bg-primary-subtle border-light-subtle text-dark';

                    $wrappedHtml = "<span class='highlight-segment px-2.5 py-1 rounded-2 border d-inline-block {$activeClass}' data-segment-id='".htmlspecialchars($segId)."' onclick='onHighlightToggle({$qId}, this)' style='cursor: pointer; margin: 2px 0; transition: all 0.2s;'>".htmlspecialchars($segText)."</span>";

                    $placeholder = "##SEGMENT_" . $segId . "##";
                    $placeholders[$placeholder] = $wrappedHtml;

                    $sanitizedPassage = str_replace($segText, $placeholder, $sanitizedPassage);
                }

                foreach ($placeholders as $placeholder => $wrappedHtml) {
                    $sanitizedPassage = str_replace($placeholder, $wrappedHtml, $sanitizedPassage);
                }

                $html .= "<div class='highlight-container' data-question-id='{$qId}'>";
                $html .= "  <div class='p-4 bg-light rounded-3 font-sans border text-dark' style='line-height: 2.2; font-size: 1.1rem;'>";
                $html .= $sanitizedPassage;
                $html .= "  </div>";
                $html .= "</div>";
                break;

            case 'bowtie':
                $leftOptions = $qData['left_options'] ?? [];
                $centerOptions = $qData['center_options'] ?? [];
                $rightOptions = $qData['right_options'] ?? [];

                $leftTarget = intval($qData['left_target_count'] ?? 1);
                $centerTarget = intval($qData['center_target_count'] ?? 1);
                $rightTarget = intval($qData['right_target_count'] ?? 1);

                $selLeft = $ansData['left'] ?? [];
                $selCenter = $ansData['center'] ?? [];
                $selRight = $ansData['right'] ?? [];

                $html .= "<div class='bowtie-container' data-question-id='{$qId}' data-left-target='{$leftTarget}' data-center-target='{$centerTarget}' data-right-target='{$rightTarget}'>";
                $html .= "  <div class='row g-3 font-sans'>";

                // Left Column
                $html .= "    <div class='col-md-4'>";
                $html .= "      <div class='p-3 border rounded-3 bg-light h-100 d-flex flex-column'>";
                $html .= "        <div class='d-flex justify-content-between align-items-center mb-2'>";
                $html .= "          <h6 class='fw-bold mb-0 text-dark'>Actions to Take</h6>";
                $html .= "          <span class='badge bg-secondary text-white px-2 py-1 small bowtie-counter-left'>".count($selLeft)." / {$leftTarget}</span>";
                $html .= "        </div>";
                $html .= "        <hr class='my-2 opacity-50'>";
                $html .= "        <div class='d-flex flex-column gap-2 mt-1 bowtie-list' data-col='left'>";
                foreach ($leftOptions as $opt) {
                    $optId = $opt['id'] ?? '';
                    $isSelected = in_array($optId, $selLeft);
                    $activeClass = $isSelected ? 'bg-primary text-white border-primary fw-bold shadow-sm' : 'bg-white text-dark hover-bg-light border-light-subtle';
                    $html .= "      <div class='p-2.5 border rounded-2 d-flex align-items-start gap-2 bowtie-option {$activeClass}' data-id='".htmlspecialchars($optId)."' onclick='onBowtieToggle({$qId}, \"left\", \"".htmlspecialchars($optId)."\")' style='font-size: 0.9rem; cursor: pointer; transition: all 0.2s;'>";
                    $html .= "        <span class='font-sans'>" . htmlspecialchars($opt['text'] ?? '') . "</span>";
                    $html .= "      </div>";
                }
                $html .= "        </div>";
                $html .= "      </div>";
                $html .= "    </div>";

                // Center Column
                $html .= "    <div class='col-md-4'>";
                $html .= "      <div class='p-3 border border-primary border-opacity-25 rounded-3 bg-light h-100 d-flex flex-column' style='background-color: #f8fafc;'>";
                $html .= "        <div class='d-flex justify-content-between align-items-center mb-2'>";
                $html .= "          <h6 class='fw-bold mb-0 text-primary'>Condition Most Likely</h6>";
                $html .= "          <span class='badge bg-primary text-white border border-primary px-2 py-1 small bowtie-counter-center'>".count($selCenter)." / {$centerTarget}</span>";
                $html .= "        </div>";
                $html .= "        <hr class='my-2 opacity-50'>";
                $html .= "        <div class='d-flex flex-column gap-2 mt-1 bowtie-list' data-col='center'>";
                foreach ($centerOptions as $opt) {
                    $optId = $opt['id'] ?? '';
                    $isSelected = in_array($optId, $selCenter);
                    $activeClass = $isSelected ? 'bg-primary text-white border-primary fw-bold shadow-sm' : 'bg-white text-dark hover-bg-light border-light-subtle';
                    $html .= "      <div class='p-2.5 border rounded-2 d-flex align-items-start gap-2 bowtie-option {$activeClass}' data-id='".htmlspecialchars($optId)."' onclick='onBowtieToggle({$qId}, \"center\", \"".htmlspecialchars($optId)."\")' style='font-size: 0.9rem; cursor: pointer; transition: all 0.2s;'>";
                    $html .= "        <span class='font-sans'>" . htmlspecialchars($opt['text'] ?? '') . "</span>";
                    $html .= "      </div>";
                }
                $html .= "        </div>";
                $html .= "      </div>";
                $html .= "    </div>";

                // Right Column
                $html .= "    <div class='col-md-4'>";
                $html .= "      <div class='p-3 border rounded-3 bg-light h-100 d-flex flex-column'>";
                $html .= "        <div class='d-flex justify-content-between align-items-center mb-2'>";
                $html .= "          <h6 class='fw-bold mb-0 text-dark'>Parameters to Monitor</h6>";
                $html .= "          <span class='badge bg-secondary text-white px-2 py-1 small bowtie-counter-right'>".count($selRight)." / {$rightTarget}</span>";
                $html .= "        </div>";
                $html .= "        <hr class='my-2 opacity-50'>";
                $html .= "        <div class='d-flex flex-column gap-2 mt-1 bowtie-list' data-col='right'>";
                foreach ($rightOptions as $opt) {
                    $optId = $opt['id'] ?? '';
                    $isSelected = in_array($optId, $selRight);
                    $activeClass = $isSelected ? 'bg-primary text-white border-primary fw-bold shadow-sm' : 'bg-white text-dark hover-bg-light border-light-subtle';
                    $html .= "      <div class='p-2.5 border rounded-2 d-flex align-items-start gap-2 bowtie-option {$activeClass}' data-id='".htmlspecialchars($optId)."' onclick='onBowtieToggle({$qId}, \"right\", \"".htmlspecialchars($optId)."\")' style='font-size: 0.9rem; cursor: pointer; transition: all 0.2s;'>";
                    $html .= "        <span class='font-sans'>" . htmlspecialchars($opt['text'] ?? '') . "</span>";
                    $html .= "      </div>";
                }
                $html .= "        </div>";
                $html .= "      </div>";
                $html .= "    </div>";

                $html .= "  </div>";
                $html .= "</div>";
                break;

            case 'fill_blank_calc':
                $unit = $qData['unit'] ?? '';
                $savedVal = $ansData['value'] ?? '';

                $html .= "<div class='fill-blank-calc-container font-sans' data-question-id='{$qId}'>";
                $html .= "  <label class='form-label text-muted small fw-semibold mb-2'>Enter your numeric answer below:</label>";
                $html .= "  <div class='input-group' style='max-width: 300px;'>";
                $html .= "    <input type='number' step='any' class='form-control border-primary text-dark bg-white' value='".htmlspecialchars($savedVal)."' placeholder='Type numeric response' oninput='onFillBlankCalcChange({$qId}, this.value)' style='font-size: 1.1rem; font-weight: 500;'>";
                if (!empty($unit)) {
                    $html .= "  <span class='input-group-text bg-light text-muted fw-bold'>".htmlspecialchars($unit)."</span>";
                }
                $html .= "  </div>";
                $html .= "</div>";
                break;

            case 'essay':
                $savedText = $ansData['text'] ?? '';
                $html .= "<div class='essay-container font-sans' data-question-id='{$qId}'>";
                $html .= "  <label class='form-label text-muted small fw-semibold mb-2'>Type your essay response below:</label>";
                $html .= "  <textarea class='form-control font-sans text-dark border-primary bg-white' rows='8' placeholder='Type detailed response here...' oninput='onEssayChange({$qId}, this.value)' style='line-height: 1.6; font-size: 1rem;'>".htmlspecialchars($savedText)."</textarea>";
                $html .= "</div>";
                break;

            default:
                $html .= "    <div class='alert alert-secondary border-0 rounded-3 mb-0'>Unsupported question type.</div>";
                break;
        }

        $html .= "    </div>";
        $html .= "  </div>";
        $html .= "</div>";

        return $html;
    }
}
