<?php include $_SERVER['DOCUMENT_ROOT'] . '/../private/security.php'; ?>
<?php
// Enable full error reportingini_set('display_errors', 1);


require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php'; // Database connection
include $_SERVER['DOCUMENT_ROOT'] . '/../private/ap_open.php';

$records_updated = 0;
$users_updated = 0;

// 🔹 Fetch ID from URL
$record_id = $_GET['id'] ?? '';

if (empty($record_id)) {
    die("❌ No ID provided in URL.");
}

// 1. Check if this record exists in records table
$checkRecord = $conn->prepare("SELECT id FROM records WHERE id = ?");
$checkRecord->bind_param("i", $record_id);
$checkRecord->execute();
$checkResult = $checkRecord->get_result();

if ($checkResult->num_rows === 0) {
    die("❌ Record with ID $record_id not found in records.");
}

// 2. Check if plan exists for this ID and price > 0
$plan_stmt = $conn->prepare("SELECT price FROM plan WHERE user_id = ?");
$plan_stmt->bind_param("s", $record_id);
$plan_stmt->execute();
$plan_result = $plan_stmt->get_result();

$update_needed = false;

if ($plan_result->num_rows === 0) {
    $update_needed = true; // No plan found
} else {
    $plan = $plan_result->fetch_assoc();
    if ($plan['price'] == 0) {
        $update_needed = true; // price is 0
    }
}

if ($update_needed) {
    // 3. Update records.status if it's active
    $update_records = $conn->prepare("UPDATE records SET status = 'pending' WHERE id = ? AND status = 'active'");
    $update_records->bind_param("i", $record_id);
    $update_records->execute();
    if ($update_records->affected_rows > 0) {
        $records_updated++;
    }

    // 4. Update users.status if it's active and id = record_id
    $update_users = $conn->prepare("UPDATE users SET status = 'pending' WHERE id = ? AND status = 'active'");
    $update_users->bind_param("i", $record_id);
    $update_users->execute();
    if ($update_users->affected_rows > 0) {
        $users_updated++;
    }
}

// 5. Final Output
// echo "<h3>✅ Update Summary</h3>";
// echo "🔷 Record ID: <strong>$record_id</strong><br>";
// echo "🟦 records table status changed to pending: <strong>$records_updated</strong><br>";
// echo "🟨 users table status changed to pending: <strong>$users_updated</strong><br>";

include $_SERVER['DOCUMENT_ROOT'] . '/../private/ap_block.php';
$conn->close();
?>
