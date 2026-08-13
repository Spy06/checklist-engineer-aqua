<?php
$pdfContent = file_get_contents(__DIR__ . '/../forms/rolling door.pdf');

// Match simple text patterns in PDF content streams (often formatted as (text) Tj or [(text)] TJ)
preg_match_all('/\(([^)]*)\)/', $pdfContent, $matches);

echo "--- Extracted raw strings from PDF containing RD ---\n";
$printed = [];
foreach ($matches[1] as $str) {
    if (preg_match('/rd\s*\d+/i', $str)) {
        $trimmed = trim($str);
        if (!isset($printed[$trimmed])) {
            echo $trimmed . "\n";
            $printed[$trimmed] = true;
        }
    }
}
