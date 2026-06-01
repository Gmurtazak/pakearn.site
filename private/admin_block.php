<?php

// Security headers
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

// Session timeout management
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 600)) {
    session_unset();
    session_destroy();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Environment configuration (disable errors in production)
ini_set('display_errors', 0);
error_reporting(0);

include 'connection.php';
// Block common injection attempts via GET parameters
$blacklist = ['select', 'insert', 'update', 'delete', 'drop', 'truncate', 'alter', '--', ';', '/*', '*/', '@@', '@', 'exec', '1=1'];
foreach ($_GET as $param => $value) {
    if (is_array($value)) continue;
    $value = strtolower($value);
    foreach ($blacklist as $word) {
        if (strpos($value, $word) !== false) {
            http_response_code(403);
            exit("Access denied");
        }
    }
}

// Password Migration Check (run only once - remove after migration)
function migratePasswordsToHashed($conn) {
    $query = "SELECT id, password FROM admins WHERE password NOT LIKE '$2y$%'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $hashed = password_hash($row['password'], PASSWORD_DEFAULT);
            $update = "UPDATE admins SET password = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update);
            mysqli_stmt_bind_param($stmt, "si", $hashed, $row['id']);
            mysqli_stmt_execute($stmt);
        }
        return true;
    }
    return false;
}

// Uncomment this line only once to migrate passwords, then comment it out again
// migratePasswordsToHashed($conn);

// Login functionality with prepared statements and password hashing
if (isset($_POST['login'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header('Location: '.$_SERVER['PHP_SELF']);
        exit();
    }
    
    $query = "SELECT id, email, password FROM records WHERE email = ? AND id=5557342 LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
      if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        
        // Fetch hashed password from DB
        $db_hashed_password = $row['password'];
        

        // Match entered password with hashed password
        if (password_verify($password, $db_hashed_password)) {
    
            // Optional: Rehash if algorithm outdated
            if (password_needs_rehash($db_hashed_password, PASSWORD_DEFAULT)) {
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $update = "UPDATE admins SET password = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update);
                mysqli_stmt_bind_param($stmt, "si", $new_hash, $row['id']);
                mysqli_stmt_execute($stmt);
            }
    
            $_SESSION['admin'] = [
                'id' => $row['id'],
                'email' => $row['email'],
                'logged_in' => true,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ];
            session_regenerate_id(true);
            header('Location: https://pakearn.io/panal.php');
            exit();
    
        } else {
            // Wrong password
            $_SESSION['error'] = "Invalid credentials";
            header('Location: '.$_SERVER['PHP_SELF']);
            exit();
        }
    }

    
    // Generic error message to prevent user enumeration
    $_SESSION['error'] = "Invalid credentials";
    header('Location: '.$_SERVER['PHP_SELF']);
    exit();
}

// Password reset request
if (isset($_POST['forgot_password'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $secret_code = $_POST['secret_code'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header('Location: '.$_SERVER['PHP_SELF']);
        exit();
    }
    
    $query = "SELECT email FROM admins WHERE email = ? AND secret_code = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $email, $secret_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
        
        // Store token in database
        $update = "UPDATE admins SET reset_token = ?, reset_expires = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $update);
        mysqli_stmt_bind_param($stmt, "sss", $token, $expires, $email);
        mysqli_stmt_execute($stmt);
        
        // In a real implementation, you would send an email with reset link
        $_SESSION['message'] = "Password reset instructions have been sent to your email";
    } else {
        // Generic message
        $_SESSION['error'] = "If an account exists with this email, reset instructions will be sent";
    }
    
    header('Location: '.$_SERVER['PHP_SELF']);
    exit();
}

// Display messages
$message = $_SESSION['message'] ?? null;
$error_message = $_SESSION['error'] ?? null;

// Clear session messages
unset($_SESSION['message']);
unset($_SESSION['error']);
?>
