<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['phone'])) {
    $id = $_POST['id'];
    $phone = $_POST['phone'];
    
    // Check if the ID exists in the records table
    $checkSql = "SELECT status FROM records WHERE id='$id'";
    $checkResult = $conn->query($checkSql);

    // Check if the phone number exists in the users table
    $phoneSql = "SELECT status FROM users WHERE ph='$phone'";
    $phoneResult = $conn->query($phoneSql);

    if ($checkResult->num_rows > 0 && $phoneResult->num_rows > 0) {
        $rowRecord = $checkResult->fetch_assoc();
        $rowUser = $phoneResult->fetch_assoc();
        if ($rowRecord['status'] === 'active' && $rowUser['status'] === 'active') {
            $error = "The record with ID $id and phone number $phone are already active.";
        } else {
            $updateRecordSql = "UPDATE records SET status='active' WHERE id='$id'";
            $updateUserSql = "UPDATE users SET status='active' WHERE ph='$phone'";
            if ($conn->query($updateRecordSql) === TRUE && $conn->query($updateUserSql) === TRUE) {
                $message = "Record and User status updated successfully. Refreshing...";
                header("refresh:2; url=update_status.php"); // Refresh the page after 2 seconds
            } else {
                $error = "Error updating record or user: " . $conn->error;
            }
        }
    } else {
        $error = "No record found with ID $id or phone number $phone.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Record and User Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .form-box {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: 20px auto;
            animation: fadeIn 1s;
        }
        .form-box form {
            display: flex;
            flex-direction: column;
        }
        .form-box input[type="text"], .form-box input[type="submit"], .form-box a {
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .form-box input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }
        .form-box input[type="submit"]:hover {
            background-color: #45a049;
        }
        .form-box a {
            background-color: #007BFF;
            color: white;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }
        .form-box a:hover {
            background-color: #0056b3;
        }
        .message, .error {
            text-align: center;
            margin: 10px 0;
            color: green;
        }
        .error {
            color: red;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <h1>Update Record and User Status</h1>
    <div class="form-box">
        <?php
        if ($error) {
            echo '<div class="error">' . $error . '</div>';
        }
        ?>
        <form method="POST" action="">
            <label for="id">Enter Record ID:</label>
            <input type="text" id="id" name="id" required>
            <label for="phone">Enter Phone Number:</label>
            <input type="text" id="phone" name="phone" required>
            <input type="submit" value="Update Status">
            <a href="index.php">Go to Pending Records</a>
        </form>
        <?php
        if ($message) {
            echo '<div class="message">' . $message . '</div>';
        }
        ?>
    </div>
    <script>
        // Hide the error message after 2 seconds
        setTimeout(function() {
            var errorDiv = document.querySelector('.error');
            if (errorDiv) {
                errorDiv.style.display = 'none';
            }
        }, 4000);
    </script>
</body>
</html>

<?php
$conn->close();
?>
