<?php
// Set this to your public_html directory path
$publicHtmlPath = $_SERVER['DOCUMENT_ROOT'];

$includeLine = "<?php include \$_SERVER['DOCUMENT_ROOT'] . '/../private/security.php'; ?>\n";

// Recursive function to process all .php files
function addSecurityInclude($dir, $includeLine) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $fullPath = $dir . DIRECTORY_SEPARATOR . $file;

        if (is_dir($fullPath)) {
            addSecurityInclude($fullPath, $includeLine); // Recurse into subfolder
        } elseif (pathinfo($fullPath, PATHINFO_EXTENSION) === 'php') {
            $contents = file_get_contents($fullPath);

            // Skip if already included
            if (strpos($contents, "private/security.php") !== false) continue;

            // Prepend the include line
            $newContents = $includeLine . $contents;
            file_put_contents($fullPath, $newContents);
            echo "✅ Updated: $fullPath\n";
        }
    }
}

// Start the update process
addSecurityInclude($publicHtmlPath, $includeLine);
echo "\n🎉 All PHP files in public_html updated.\n";
?>
