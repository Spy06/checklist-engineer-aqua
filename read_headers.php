<?php
$dir = 'd:\Projects\PHP Projects\Laravel\checklist-engineer-aqua\Dari Pak Dadang\Jawaban';
$out = '';
foreach(glob($dir . '/*.csv') as $file) {
    $f = fopen($file, 'r');
    $header = fgetcsv($f);
    fclose($f);
    $out .= "=====================================\n";
    $out .= basename($file) . "\n";
    
    // Some CSVs have metadata columns before actual options
    $filtered_header = [];
    $ignore = ['Cap waktu', 'Nama pemeriksaan', 'Tanggal pemeriksaan', 'Area Pemeriksaan', 'Cimex', 'Existing', 'Gede', 'Sumber', 'Catatan'];
    foreach($header as $col) {
        if(!in_array($col, $ignore) && !empty(trim($col))) {
            $filtered_header[] = $col;
        }
    }
    
    $out .= implode("\n", $filtered_header) . "\n\n";
}
file_put_contents('headers.txt', $out);
