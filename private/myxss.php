<?php
/**
 * check_xss_and_update.php
 * Finds all records with potential HTML/XSS code,
 * lists them, and replaces the affected fields with "Hello".
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// ───── Get current database ─────
$dbNameResult = $conn->query('SELECT DATABASE()');
if (!$dbNameResult) {
    die('❌ Could not determine the current database.');
}
$dbName = $dbNameResult->fetch_row()[0];

// ───── Get all table names ─────
$tablesRes = $conn->query(
    "SELECT table_name FROM information_schema.tables WHERE table_schema = '{$dbName}'"
);

$tables = [];
while ($row = $tablesRes->fetch_assoc()) {
    $tables[] = $row['table_name'];
}

$regex = '/<[^>]+>/i'; // Any HTML tag
$totalUpdates = 0;
$report = [];

foreach ($tables as $table) {
    $columnsRes = $conn->query("SHOW COLUMNS FROM `{$table}`");
    $textCols = [];
    $primaryKey = null;

    while ($col = $columnsRes->fetch_assoc()) {
        $type = strtolower($col['Type']);
        if (preg_match('/char|text|blob|binary|enum|set/', $type)) {
            $textCols[] = $col['Field'];
        }
        if ($col['Key'] === 'PRI' && $primaryKey === null) {
            $primaryKey = $col['Field'];
        }
    }

    if (!$textCols) {
        continue;
    }

    $selectCols = ($primaryKey ? "`{$primaryKey}`, " : '') .
                  implode(', ', array_map(fn($c) => "`$c`", $textCols));

    $dataRes = $conn->query("SELECT {$selectCols} FROM `{$table}`");

    $flaggedIDs = [];

    while ($row = $dataRes->fetch_assoc()) {
        $id = $primaryKey ? $row[$primaryKey] : null;
        $updates = [];

        foreach ($textCols as $col) {
            if (!empty($row[$col]) && preg_match($regex, $row[$col])) {
                $updates[] = "`$col`='Hello'";
            }
        }

        if ($updates && $id !== null) {
            $updateSql = "UPDATE `$table` SET " . implode(", ", $updates) .
                         " WHERE `$primaryKey`='{$id}'";
            if ($conn->query($updateSql)) {
                $flaggedIDs[] = $id;
                $totalUpdates++;
            }
        }
    }

    if (!empty($flaggedIDs)) {
        $report[$table] = $flaggedIDs;
    }
}

// ───── Output report ─────
echo "<pre style='font-family:monospace'>";
if (empty($report)) {
    echo "✅ No XSS/HTML found. No updates made.\n";
} else {
    foreach ($report as $table => $ids) {
        echo "Table: {$table}\n";
        echo "  Updated Records: " . count($ids) . "\n";
        echo "  IDs: " . implode(', ', $ids) . "\n\n";
    }
    echo "════════════════════════════════════\n";
    echo "Total Records Updated: {$totalUpdates}\n";
}
echo "</pre>";

$conn->close();
