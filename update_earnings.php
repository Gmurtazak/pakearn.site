<?php
include 'private/unblock_earnings.php';

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

require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
date_default_timezone_set('Asia/Karachi');

$id = $_POST['id'];
$box = $_POST['box']; // Which box (box1, box2, etc.) is being clicked

// Use MySQLi with exceptions for easier transaction handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn->begin_transaction();

    // ✅ STEP 0: Check if box is already clicked (0)
    $checkStmt = $conn->prepare("SELECT $box FROM records WHERE id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult && $rowBox = $checkResult->fetch_assoc()) {
        if ($rowBox[$box] == 0) {
            throw new Exception("Already earned from this box today!");
        }
    }
    $checkStmt->close();

    // Fetch level from the level table
    $stmt = $conn->prepare("SELECT level FROM level WHERE us_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result || $result->num_rows == 0) {
        throw new Exception("Error fetching level: User has no level entry.");
    }
    $row = $result->fetch_assoc();
    $level = $row['level'];
    $stmt->close();

    // Fetch price from the plan table
    $stmt = $conn->prepare("SELECT price FROM plan WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result || $result->num_rows == 0) {
        throw new Exception("Error fetching price: No plan found for this user.");
    }
    $row = $result->fetch_assoc();
    $price = $row['price'];
    $stmt->close();

    // Fetch base and reward_increment from the price_values table
    $stmt = $conn->prepare("SELECT base, reward_increment FROM price_values WHERE price = ?");
    $stmt->bind_param("s", $price);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result || $result->num_rows == 0) {
        throw new Exception("Error fetching base price values: Plan '$price' not defined in price_values.");
    }
    $row = $result->fetch_assoc();
    $base = $row['base'];
    $re = $row['reward_increment'];
    $stmt->close();

    $ads_increment = 0;
    $total_earn = 0;
    $reward_increment = 0;

    // Handle level 10 separately
    if ($level == 10) {
        $total_earn = 900;
        $ads_increment = 900;
        $reward_increment = 0;
    } else if ($level >= 1 && $level <= 9) {
        $ads_increment = ($base - 1) + $level;
        $total_earn = $base + ($level - 1) * 2;
        $reward_increment = $re + ($level - 1) * 10;
    } else {
        throw new Exception("Invalid level detected ($level). Level must be between 1 and 10.");
    }

    // ✅ STEP 1: Get old balance and old ads_earning BEFORE earnings update
    $old_balance = 0;
    $old_ads_earning = 0;
    $stmt = $conn->prepare("SELECT total_earning, ads_earning FROM earnings WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $getOld = $stmt->get_result();
    if ($getOld && $getOld->num_rows > 0) {
        $rowOld = $getOld->fetch_assoc();
        $old_balance = $rowOld['total_earning'];
        $old_ads_earning = $rowOld['ads_earning'];
    }
    $stmt->close();

    $new_ads_earning = $old_ads_earning + $ads_increment;

    // ✅ Update earnings securely (Calculate on server)
    $stmt = $conn->prepare("SELECT id FROM earnings WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = ($result && $result->num_rows > 0);
    $stmt->close();

    if ($exists) {
        $stmt = $conn->prepare("UPDATE earnings SET total_earning = total_earning + ?, ads_earning = ? WHERE id = ?");
        $stmt->bind_param("idi", $total_earn, $new_ads_earning, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO earnings (id, total_earning, ads_earning) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $id, $total_earn, $new_ads_earning);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare("UPDATE level SET reward = reward + ? WHERE us_id = ?");
    $stmt->bind_param("ii", $reward_increment, $id);
    $stmt->execute();
    $stmt->close();

    // ✅ STEP 1.5: Set box to 0 BEFORE anything else (to prevent double clicks)
    $stmt = $conn->prepare("UPDATE records SET $box = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // ✅ STEP 2: Now get the updated (new) balance
    $new_balance = 0;
    $stmt = $conn->prepare("SELECT total_earning FROM earnings WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $getNew = $stmt->get_result();
    if ($getNew && $getNew->num_rows > 0) {
        $rowNew = $getNew->fetch_assoc();
        $new_balance = $rowNew['total_earning'];
    }
    $stmt->close();

    // ✅ STEP 3: Insert into notify
    $total_add = $new_balance - $old_balance;
    $created_at = date("Y-m-d H:i:s");
    $message = "earned from $box";

    $stmt = $conn->prepare("INSERT INTO notify (user_id, name, message, old_earn, total_add, current_earn, created_at)
                            VALUES (?, NULL, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isddds", $id, $message, $old_balance, $total_add, $new_balance, $created_at);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'error' => "Transaction failed: " . $e->getMessage()]);
}

include 'private/block_earnings.php';
$conn->close();
?>