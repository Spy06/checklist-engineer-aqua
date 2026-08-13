<?php

function testTemp($overrideTemp = true) {
    echo "--- Testing with overrideTemp = " . ($overrideTemp ? 'true' : 'false') . " ---\n";
    
    if ($overrideTemp) {
        $tempDir = __DIR__ . '/../storage/app/temp';
        if (!file_exists($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }
        putenv("TMP={$tempDir}");
        putenv("TEMP={$tempDir}");
        putenv("TMPDIR={$tempDir}");
    } else {
        // Reset or don't set
        putenv("TMP");
        putenv("TEMP");
        putenv("TMPDIR");
    }
    
    echo "sys_get_temp_dir(): " . sys_get_temp_dir() . "\n";
    echo "env TMP: " . getenv('TMP') . "\n";
    
    try {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        if ($tempFile === false) {
            echo "tempnam() failed!\n";
        } else {
            echo "tempnam() created: " . $tempFile . "\n";
            @unlink($tempFile);
        }
        
        // Write 3MB to php://temp
        echo "Writing 3MB to php://temp...\n";
        $stream = fopen('php://temp', 'r+');
        if (!$stream) {
            echo "Failed to open php://temp\n";
            return;
        }
        
        $data = str_repeat('A', 3 * 1024 * 1024); // 3MB
        $written = fwrite($stream, $data);
        if ($written === false) {
            echo "fwrite() failed!\n";
        } else {
            echo "fwrite() succeeded! Wrote " . $written . " bytes.\n";
        }
        fclose($stream);
    } catch (\Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

testTemp(true);
testTemp(false);
