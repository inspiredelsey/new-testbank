<?php
$files = array_merge(
    glob(__DIR__ . '/testbank/admin/models/*.php'),
    glob(__DIR__ . '/testbank/site/models/*.php'),
    glob(__DIR__ . '/testbank/includes/*.php'),
    glob(__DIR__ . '/testbank/admin/controllers/*.php'),
    glob(__DIR__ . '/testbank/site/controllers/*.php')
);

foreach ($files as $file) {
    $content = file_get_contents($file);
    if (preg_match_all('/(?:prepare|query|exec)\s*\(\s*["\'](.*?)["\']\s*\)/s', $content, $matches)) {
        echo "=== FILE: $file ===\n";
        foreach ($matches[1] as $m) {
            $sql = preg_replace('/\s+/', ' ', trim($m));
            if (strlen($sql) > 0) {
                echo "  SQL: $sql\n";
            }
        }
    }
}
