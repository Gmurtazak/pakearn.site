<?php
include 'connection.php';
$message = ""; // Message variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account_type = $_POST['account_type'];
    $account_name = $_POST['account_name'];
    $account_number = $_POST['account_number'];

    include 'unblock_pay2.php';
    // Use prepared statement for safety
    $stmt = $conn->prepare("UPDATE payment_accounts SET account_name = ?, account_number = ? WHERE account_type = ?");
    $stmt->bind_param("sss", $account_name, $account_number, $account_type);

    if ($stmt->execute()) {
        $message = "Account details updated successfully";
    } else {
        $message = "Error updating account details: " . $stmt->error;
    }
    include 'block_pay2.php';
    $stmt->close();
}
?>
