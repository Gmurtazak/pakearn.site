<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
date_default_timezone_set("Asia/Karachi");
$today = new DateTime();
$falseCount = 0;

echo "<h2>False Records (Updated):</h2><ul>";

// Step 1: Get all active records
$sql = "SELECT id FROM records WHERE status = 'active' ORDER BY id ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recordId = $row['id'];
        $lastActiveUserId = null;
        $checkForFalse = false;

        // Step 2: Find active users where reff_id = recordId
        $userSql = "SELECT id FROM users WHERE reff_id = ? AND status = 'active'";
        $stmt = $conn->prepare($userSql);
        $stmt->bind_param("i", $recordId);
        $stmt->execute();
        $userResult = $stmt->get_result();

        if ($userResult->num_rows > 0) {
            // Step 3: Get the last active user ID
            $ids = [];
            while ($userRow = $userResult->fetch_assoc()) {
                $ids[] = $userRow['id'];
            }
            $lastActiveUserId = max($ids);

            // Step 4: Get join_hoi from records table of that user
            $joinSql = "SELECT join_hoi FROM records WHERE id = ?";
            $stmt2 = $conn->prepare($joinSql);
            $stmt2->bind_param("i", $lastActiveUserId);
            $stmt2->execute();
            $joinResult = $stmt2->get_result();

            if ($joinResult->num_rows > 0) {
                $joinRow = $joinResult->fetch_assoc();
                $joinDate = new DateTime($joinRow['join_hoi']);
                $interval = $joinDate->diff($today)->days;

                if ($interval >= 8) {
                    $checkForFalse = true;
                }
            }
        } else {
            // No users found → fallback to own join_hoi
            $ownSql = "SELECT join_hoi FROM records WHERE id = ?";
            $stmt3 = $conn->prepare($ownSql);
            $stmt3->bind_param("i", $recordId);
            $stmt3->execute();
            $ownResult = $stmt3->get_result();

            if ($ownResult->num_rows > 0) {
                $ownRow = $ownResult->fetch_assoc();
                $joinDate = new DateTime($ownRow['join_hoi']);
                $interval = $joinDate->diff($today)->days;

                if ($interval >= 8) {
                    $checkForFalse = true;
                }
            }
        }

        // Step 5: Final check → if false, update stat, ads_date, ads_date_start
        if ($checkForFalse) {
            $statSql = "SELECT stat FROM records WHERE id = ?";
            $stmt4 = $conn->prepare($statSql);
            $stmt4->bind_param("i", $recordId);
            $stmt4->execute();
            $statResult = $stmt4->get_result();

            if ($statResult->num_rows > 0) {
                $statRow = $statResult->fetch_assoc();
                if ($statRow['stat'] != 0) {
                    // UPDATE
                    $updateSql = "UPDATE records SET stat = 0, ads_date = NULL, ads_date_start = NULL WHERE id = ?";
                    $stmtUpdate = $conn->prepare($updateSql);
                    $stmtUpdate->bind_param("i", $recordId);
                    $stmtUpdate->execute();

                    echo "<li>ID: $recordId - <b>false (updated)</b></li>";
                    $falseCount++;
                }
            }
        }
    }

    echo "</ul><h3>Total False Records Updated: $falseCount</h3>";
} else {
    echo "No active records found.";
}

$conn->close();
?>
