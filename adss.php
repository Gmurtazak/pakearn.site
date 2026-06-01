<?php include $_SERVER['DOCUMENT_ROOT'] . '/../private/security.php'; ?>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Get `id` from URL
if (!isset($_GET['id'])) {
    return;
}
$id = intval($_GET['id']); // Sanitize input

// Fetch timer_value in minutes from timer_table WHERE id = 1
$timerSql = "SELECT timer_value FROM timer_table WHERE id = 1";
$timerResult = mysqli_query($conn, $timerSql);
if (!$timerResult || mysqli_num_rows($timerResult) == 0) {
    return;
}
$timerRow = mysqli_fetch_assoc($timerResult);
$timerMinutes = intval($timerRow['timer_value']);
$timerDays = floor($timerMinutes / (60 * 24)); // Optional conversion to days

// Fetch record from records table
$recordSql = "SELECT stat, ads_date, ads_date_start FROM records WHERE id = ?";
$stmt = $conn->prepare($recordSql);
$stmt->bind_param("i", $id);
$stmt->execute();
$recordResult = $stmt->get_result();
if ($recordResult->num_rows === 0) {
    return;
}
$record = $recordResult->fetch_assoc();

$stat = intval($record['stat']);
$ads_date = $record['ads_date'];
$ads_date_start = $record['ads_date_start'];

date_default_timezone_set('Asia/Karachi'); // Set Pakistani timezone
$now = date('Y-m-d H:i:s');

// Continue only if stat is 1
if ($stat == 1) {
    if (is_null($ads_date)) {
        // If ads_date is NULL, set ads_date_start to now and ads_date to now + timer
        $newAdsDate = date('Y-m-d H:i:s', strtotime("+$timerMinutes minutes"));
        $updateSql = "UPDATE records SET ads_date_start = ?, ads_date = ? WHERE id = ?";
        $stmt2 = $conn->prepare($updateSql);
        $stmt2->bind_param("ssi", $now, $newAdsDate, $id);
        $stmt2->execute();
    } else {
        // If ads_date is already set, calculate the difference with ads_date_start
        $startDate = new DateTime($ads_date_start);
        $endDate = new DateTime($ads_date);
        $diff = $startDate->diff($endDate);
        $diffMinutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

        // Adjust ads_date to match exact timerMinutes
        if ($diffMinutes != $timerMinutes) {
            $adjustedAdsDate = date('Y-m-d H:i:s', strtotime($ads_date_start . " +$timerMinutes minutes"));
            $updateSql = "UPDATE records SET ads_date = ? WHERE id = ?";
            $stmt3 = $conn->prepare($updateSql);
            $stmt3->bind_param("si", $adjustedAdsDate, $id);
            $stmt3->execute();
        }
    }
}
?>