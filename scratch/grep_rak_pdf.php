<?php
$pdfContent = file_get_contents(__DIR__ . '/../forms/rak facility.pdf');

preg_match_all('/\(([^)]*)\)/', $pdfContent, $matches);

echo "--- Extracted raw strings from PDF ---\n";
$printed = [];
foreach ($matches[1] as $str) {
    $trimmed = trim($str);
    if (strlen($trimmed) > 2) {
        if (!isset($printed[$trimmed])) {
            echo $trimmed . "\n";
            $printed[$trimmed] = true;
        }
    }
}
