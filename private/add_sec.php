<?php
// Block common SQL injection attempts via GET parameters (case-insensitive)
$blacklist = ['select', 'insert', 'update', 'delete', 'drop', 'truncate', 'alter', '--', ';', '/*', '*/', '@@', '@', 'exec', '1=1'];

foreach ($_GET as $param => $value) {
    if (is_array($value)) continue;

    $value = strtolower($value); // convert input to lowercase

    foreach ($blacklist as $word) {
        if (strpos($value, $word) !== false) {
            http_response_code(403);
            exit("Blocked: Malicious keyword '$word' detected in URL.");
        }
    }
}
?>
<?php
// File: add_final_protection.php
// No backups version - adds GET parameter protection only

$protectionCode = '<?php
// Block common SQL injection attempts via GET parameters (case-insensitive)
$blacklist = [\'select\', \'insert\', \'update\', \'delete\', \'drop\', \'truncate\', \'alter\', \'--\', \';\', \'/*\', \'*/\', \'@@\', \'@\', \'exec\', \'1=1\'];

foreach ($_GET as $param => $value) {
    if (is_array($value)) continue;

    $value = strtolower($value); // convert input to lowercase

    foreach ($blacklist as $word) {
        if (strpos($value, $word) !== false) {
            http_response_code(403);
            exit("Blocked: Malicious keyword \'$word\' detected in URL.");
        }
    }
}
?>';

function addFinalProtection($dir) {
    global $protectionCode;
    
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            
            // Skip if already protected
            if (strpos($content, '$blacklist = [\'select\'') !== false) {
                echo "✓ SKIPPED: {$file->getPathname()}<br>";
                continue;
            }
            
            // Add protection code at start
            file_put_contents($file->getPathname(), $protectionCode . "\n" . $content);
            echo "✓ PROTECTED: {$file->getPathname()}<br>";
            $count++;
        }
    }
    return $count;
}

// Execute
header('Content-Type: text/html; charset=utf-8');
echo "<h3>Final Protection Adder</h3>";
echo "<p>Adding GET parameter security to all PHP files</p>";

$main_dir = '/home/xxx/public_html';  // Hostinger path
if (!file_exists($main_dir)) {
    $main_dir = __DIR__; // Fallback
}

// Process public_html
echo "<h4>Main Directory: {$main_dir}</h4>";
$main_count = addFinalProtection($main_dir);

// Process panal
$panal_dir = "{$main_dir}/panal";
echo "<h4>Subfolder: {$panal_dir}</h4>";
if (file_exists($panal_dir)) {
    $panal_count = addFinalProtection($panal_dir);
} else {
    echo "⚠ panal folder not found<br>";
    $panal_count = 0;
}

echo "<h4>Results:</h4>";
echo "• Total files protected: " . ($main_count + $panal_count) . "<br>";
echo "<br><strong style='color:red'>Important:</strong> No backups were created!<br>";
echo "<strong style='color:green'>Complete!</strong> Delete this script immediately after use.";
?>