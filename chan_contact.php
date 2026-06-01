<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
// Include connection.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Fetch current link
$current_link = 'No link set';
$query = "SELECT whatsapp_link FROM contacts WHERE id = 1";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $current_link = $row['whatsapp_link'];
}

// Handle form submit
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_link = trim($_POST['whatsapp_link']);
    if (!empty($new_link)) {
        $update_query = "UPDATE contacts SET whatsapp_link = ? WHERE id = 1";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "s", $new_link);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Link updated successfully!";
            $current_link = $new_link;
        } else {
            $error = "Update failed!";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Link cannot be empty!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update WhatsApp Link</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f0f0; padding: 20px; text-align: center; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        input[type="text"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #ff6b6b; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #ff5252; }
        .message { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Update WhatsApp Channel Link</h2>
        <?php if (isset($message)): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <p><strong>Current Link:</strong> <?php echo htmlspecialchars($current_link); ?></p>
        
        <form method="POST">
            <input type="text" name="whatsapp_link" placeholder="Enter new WhatsApp link" value="<?php echo htmlspecialchars($current_link); ?>" required>
            <br>
            <button type="submit">Update Link</button>
        </form>
        
        <p><a href="panal.php">Back to Profile</a></p> <!-- Ya jo bhi page -->
    </div>
</body>
</html>