<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'connection.php';

/*
|--------------------------------------------------------------------------
| Get Current Database
|--------------------------------------------------------------------------
*/

$dbResult = $conn->query("SELECT DATABASE()");

if (!$dbResult) {
    die("Database not found");
}

$dbName = $dbResult->fetch_row()[0];

/*
|--------------------------------------------------------------------------
| Get All Tables
|--------------------------------------------------------------------------
*/

$tables = [];

$tablesRes = $conn->query("
    SELECT TABLE_NAME 
    FROM information_schema.tables 
    WHERE table_schema = '$dbName'
");

if (!$tablesRes) {
    die("Failed to fetch tables");
}

while ($row = $tablesRes->fetch_assoc()) {

    // Safe fetch
    $tables[] = array_values($row)[0];
}

/*
|--------------------------------------------------------------------------
| Patterns To Detect
|--------------------------------------------------------------------------
*/

$patterns = [

    // XSS
    '/<script\b[^>]*>(.*?)<\/script>/is',
    '/<[^>]+>/i',
    '/javascript:/i',
    '/onerror\s*=/i',
    '/onload\s*=/i',

    // SQL Injection
    '/\bUNION\b/i',
    '/\bSELECT\b/i',
    '/\bINSERT\b/i',
    '/\bUPDATE\b/i',
    '/\bDELETE\b/i',
    '/\bDROP\b/i',
    '/\bALTER\b/i',
    '/\bTRUNCATE\b/i',
    '/\bOR\s+1=1\b/i',
    '/--/',
    '/;/',
];

/*
|--------------------------------------------------------------------------
| Start Scan
|--------------------------------------------------------------------------
*/

$totalUpdated = 0;
$report = [];

foreach ($tables as $table) {

    /*
    |--------------------------------------------------------------------------
    | Get Columns
    |--------------------------------------------------------------------------
    */

    $columnsRes = $conn->query("SHOW COLUMNS FROM `$table`");

    if (!$columnsRes) {
        continue;
    }

    $textCols = [];
    $primaryKey = null;

    while ($col = $columnsRes->fetch_assoc()) {

        $type = strtolower($col['Type']);

        // Detect text columns
        if (preg_match('/char|text|blob|enum|set/', $type)) {
            $textCols[] = $col['Field'];
        }

        // Detect primary key
        if ($col['Key'] === 'PRI' && $primaryKey === null) {
            $primaryKey = $col['Field'];
        }
    }

    // Skip if no text columns or no primary key
    if (empty($textCols) || !$primaryKey) {
        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | Select Data
    |--------------------------------------------------------------------------
    */

    $selectCols = "`$primaryKey`, ";

    $temp = [];

    foreach ($textCols as $c) {
        $temp[] = "`$c`";
    }

    $selectCols .= implode(", ", $temp);

    $query = "SELECT $selectCols FROM `$table`";

    $dataRes = $conn->query($query);

    if (!$dataRes) {
        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | Scan Rows
    |--------------------------------------------------------------------------
    */

    while ($row = $dataRes->fetch_assoc()) {

        $id = $row[$primaryKey];

        $updates = [];

        foreach ($textCols as $col) {

            if (!isset($row[$col]) || $row[$col] === '') {
                continue;
            }

            $value = $row[$col];

            $dangerFound = false;

            foreach ($patterns as $pattern) {

                if (preg_match($pattern, $value)) {
                    $dangerFound = true;
                    break;
                }
            }

            // If suspicious data found
            if ($dangerFound) {

                // Replace with safe text
                $updates[] = "`$col`='[BLOCKED]'";

                // Save report
                $report[$table][] = [
                    'id' => $id,
                    'column' => $col,
                    'value' => $value
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Update Row
        |--------------------------------------------------------------------------
        */

        if (!empty($updates)) {

            $updateSql = "
                UPDATE `$table`
                SET " . implode(", ", $updates) . "
                WHERE `$primaryKey` = ?
            ";

            $stmt = $conn->prepare($updateSql);

            if ($stmt) {

                $stmt->bind_param("s", $id);

                if ($stmt->execute()) {
                    $totalUpdated++;
                }

                $stmt->close();
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Output Report
|--------------------------------------------------------------------------
*/

echo "<pre style='font-family:monospace;'>";

if (empty($report)) {

    echo "No suspicious data found.";

} else {

    foreach ($report as $table => $records) {

        echo "=====================================\n";
        echo "TABLE: $table\n";
        echo "=====================================\n\n";

        foreach ($records as $r) {

            echo "ID      : " . $r['id'] . "\n";
            echo "COLUMN  : " . $r['column'] . "\n";
            echo "VALUE   : " . $r['value'] . "\n";
            echo "-------------------------------------\n";
        }

        echo "\n";
    }

    echo "=====================================\n";
    echo "TOTAL UPDATED RECORDS: $totalUpdated\n";
}

echo "</pre>";

$conn->close();

?>