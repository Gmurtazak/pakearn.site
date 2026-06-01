<?php include $_SERVER['DOCUMENT_ROOT'] . '/../private/security.php'; ?>
<?php
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");

// Secure session configuration
session_start([
    'cookie_lifetime' => 600, // 30 minutes
    'cookie_secure' => true,   // Only send over HTTPS
    'cookie_httponly' => true, // Prevent JavaScript access
    'use_strict_mode' => true  // Prevent session fixation
]);

// Check if user is logged in
if (!isset($_SESSION['admin']['logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

// Verify session security
if (
    $_SESSION['admin']['ip'] !== $_SERVER['REMOTE_ADDR'] ||
    $_SESSION['admin']['user_agent'] !== $_SERVER['HTTP_USER_AGENT']
) {
    session_unset();
    session_destroy();
    header('Location: admin_login.php');
    exit();
}

// Session timeout management
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 600)) {
    session_unset();
    session_destroy();
    header('Location: admin_login.php');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Block common SQL injection attempts via GET parameters (case-insensitive)
$blacklist = ['select', 'insert', 'update', 'delete', 'drop','post', 'truncate', 'alter', '--', ';', '/*', '*/', '@@', '@', 'exec', '1=1'];

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
?>
<?php
// Include your connection file
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Check if the form is submitted
if (isset($_POST['submit'])) {
    // Get the file information
    $file_name = $_FILES['apk_file']['name'];
    $file_tmp = $_FILES['apk_file']['tmp_name'];
    
    // Define the directory where the file will be stored
    $upload_dir = 'apk/';
    
    // Make sure the directory exists, if not create it
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Define the target file path
    $file_path = $upload_dir . basename($file_name);
    
    // Only allow APK files
    $file_type = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    if ($file_type != 'apk') {
        echo "Only APK files are allowed.";
        exit;
    }

    // Move the uploaded file to the server
    if (move_uploaded_file($file_tmp, $file_path)) {
        // Prepare an SQL query to insert file details into the database
        $sql = "INSERT INTO apk_uploads (file_name, file_path) VALUES (?, ?)";
        
        // Prepare the statement
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $file_name, $file_path);
        
        // Execute the statement
        if ($stmt->execute()) {
            echo "File uploaded and stored in the database successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }
        
        // Close the statement
        $stmt->close();
    } else {
        echo "File upload failed.";
    }
}

// Close the database connection
$conn->close();
?>

<!-- HTML Form to upload APK file -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload APK File</title>
</head>
<body>
    <h2>Upload APK File</h2>
    <form action="upload_apk.php" method="POST" enctype="multipart/form-data">
        <label for="apk_file">Choose APK File:</label>
        <input type="file" name="apk_file" id="apk_file" accept=".apk" required>
        <br><br>
        <input type="submit" name="submit" value="Upload">
    </form>
</body>
</html>
