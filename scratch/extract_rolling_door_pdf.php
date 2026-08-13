<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pdfPath = __DIR__ . '/../forms/rolling door.pdf';

if (!file_exists($pdfPath)) {
    echo "PDF not found at: {$pdfPath}\n";
    exit;
}

try {
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($pdfPath);
    $text = $pdf->getText();
    
    echo "--- Extracted PDF Text ---\n";
    // Look for lines containing "RD " or "RD"
    $lines = explode("\n", $text);
    foreach ($lines as $line) {
        if (preg_match('/rd\s*\d+/i', $line)) {
            echo trim($line) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
