<?php
$rootDirectory = __DIR__; // Current directory or specify your root path
$searchPatterns = [
    "include 'connection.php';",
    "include '../connection.php';",
    'include "connection.php";',
    'include "../connection.php";',
    "include('connection.php');",
    "include('../connection.php');",
    'include("connection.php");',
    'include("../connection.php");'
];
$replacement = 'include $_SERVER[\'DOCUMENT_ROOT\'] . \'/../private/connection.php\';';

$totalFound = 0;
$totalReplaced = 0;
$processedFiles = 0;

function processDirectory($dir, $searchPatterns, $replacement, &$totalFound, &$totalReplaced, &$processedFiles) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            processDirectory($path, $searchPatterns, $replacement, $totalFound, $totalReplaced, $processedFiles);
        } else {
            // Only process PHP files (you can add other extensions if needed)
            if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $processedFiles++;
                $content = file_get_contents($path);
                $originalContent = $content;
                
                foreach ($searchPatterns as $pattern) {
                    $count = substr_count($content, $pattern);
                    if ($count > 0) {
                        $totalFound += $count;
                        $content = str_replace($pattern, $replacement, $content, $replaceCount);
                        $totalReplaced += $replaceCount;
                    }
                }
                
                if ($content !== $originalContent) {
                    file_put_contents($path, $content);
                    echo "Updated: $path\n";
                }
            }
        }
    }
}

echo "Starting replacement process...\n";
processDirectory($rootDirectory, $searchPatterns, $replacement, $totalFound, $totalReplaced, $processedFiles);
echo "\nProcess completed.\n";
echo "Files processed: $processedFiles\n";
echo "Total occurrences found: $totalFound\n";
echo "Total replacements made: $totalReplaced\n";
?>