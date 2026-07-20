<?php
/**
 * Real-time question preview viewport.
 * Simply calls the dynamic QuestionRenderer to output beautiful, type-specific elements.
 */
if (isset($question)) {
    echo QuestionRenderer::render($question);
} else {
    echo "<div class='alert alert-danger'>Preview load warning: question instance unavailable.</div>";
}
?>
