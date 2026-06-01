<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php'; // Include your database connection file

// Fetch the current URL
$query = "SELECT url FROM single_link LIMIT 1";
$result = mysqli_query($conn, $query);
$link = mysqli_fetch_assoc($result);

// Update the URL in the database
if (isset($_POST['update_link'])) {
    $url = $_POST['url'];
    include 'private/unblock_sin.php';

    $query = "UPDATE single_link SET url = '$url'";

    if (mysqli_query($conn, $query)) {
        $success_message = "URL updated successfully!";
    } else {
        $error_message = "Failed to update URL. Please try again.";
    }
}
include 'private/block_sin.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update URL</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .update-container {
            background-color: #FFFFFF;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }
        .update-container h2 {
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        .update-container input[type="url"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        .update-container button {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        .update-container button:hover {
            background-color: #2980b9;
        }
        .update-container .error-message,
        .update-container .success-message {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
        }
        .update-container .error-message {
            background-color: #e74c3c;
            color: white;
        }
        .update-container .success-message {
            background-color: #2ecc71;
            color: white;
        }
        @media (max-width: 600px) {
            .update-container {
                padding: 20px;
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="update-container">
        <h2>Update URL</h2>
        <?php if (isset($error_message)) { echo "<div class='error-message'>$error_message</div>"; } ?>
        <?php if (isset($success_message)) { echo "<div class='success-message'>$success_message</div>"; } ?>
        <form method="POST" action="">
            <input type="url" name="url" placeholder="URL" value="<?php echo isset($link['url']) ? $link['url'] : ''; ?>" required>
            <button type="submit" name="update_link">Update URL</button>
        </form>
    </div>
</body>
</html>
