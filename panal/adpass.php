<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';


$user_id = $_SESSION['user_id'];
$message = '';

// Fetch user data
$stmt = $conn->prepare("SELECT phone FROM panal WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($phone);
$stmt->fetch();
$stmt->close();

// Update user data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPhone = $_POST['phone'];
    $newPassword = $_POST['password'];
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("UPDATE panal SET phone = ?, password = ? WHERE id = ?");
    $stmt->bind_param("ssi", $newPhone, $hashedPassword, $user_id);

    if ($stmt->execute()) {
        $message = "User information updated successfully.";
        $phone = $newPhone; // Update the current phone number displayed
    } else {
        $message = "Failed to update user information.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .update-container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
        .update-container h1 {
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }
        .update-container form {
            display: flex;
            flex-direction: column;
        }
        .update-container input {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .update-container input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .update-container input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .message {
            color: green;
            margin-bottom: 15px;
            text-align: center;
        }
        .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="update-container">
        <h1>Update User Information</h1>
        <?php if ($message) { echo '<p class="message">' . $message . '</p>'; } ?>
        <form action="update_user.php" method="post">
            <label for="phone">Phone:</label>
            <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
            <label for="password">New Password:</label>
            <input type="password" name="password" id="password" required>
            <input type="submit" value="Update">
        </form>
    </div>
</body>
</html>
