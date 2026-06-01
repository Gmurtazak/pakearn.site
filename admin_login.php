<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('connection.php');

// ================= LOGIN =================
if (isset($_POST['login'])) {

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT * FROM admins WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {

        $row = mysqli_fetch_assoc($result);

        // VERIFY HASHED PASSWORD
        if (password_verify($password, $row['password'])) {

            $_SESSION['admin'] = $email;
            header('Location: panal.php');
            exit();

        } else {
            $_SESSION['error'] = "Your email or password is wrong";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }

    } else {
        $_SESSION['error'] = "Your email or password is wrong";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}


// ================= FORGOT PASSWORD =================
// ⚠️ NOTE: hashed password recover nahi hota, only reset system hota hai
if (isset($_POST['forgot_password'])) {

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $secret_code = mysqli_real_escape_string($conn, $_POST['secret_code']);

    $query = "SELECT * FROM admins WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {

        $row = mysqli_fetch_assoc($result);

        if ($row['secret_code'] === $secret_code) {

            $_SESSION['retrieved_password'] = "Password cannot be shown (hashed). Please reset it.";

        } else {
            $_SESSION['error'] = "Sorry, your secret code is wrong";
        }

    } else {
        $_SESSION['error'] = "Email not found";
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// ================= MESSAGES =================
$password_message = isset($_SESSION['retrieved_password']) ? $_SESSION['retrieved_password'] : null;
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : null;

unset($_SESSION['retrieved_password']);
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link rel="shortcut icon" type="x-icon" href="hum.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>

<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f7f7f7;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}

.login-container {
    background-color: #8E44AD;
    padding: 40px 30px;
    border-radius: 10px;
    text-align: center;
    width: 350px;
    color: white;
}

input {
    width: 90%;
    padding: 12px;
    margin: 10px 0;
    border-radius: 6px;
    border: none;
    font-size: 16px;
}

button {
    width: 100%;
    padding: 12px;
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

button:hover {
    background-color: #218838;
}

.error-message {
    background-color: #e74c3c;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 10px;
}

.password-message {
    background-color: #28a745;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 10px;
}

.forgot-link {
    color: white;
    text-decoration: underline;
    cursor: pointer;
}

.forgot-container {
    display: none;
    margin-top: 15px;
}

.logo {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: white;
    margin: 0 auto 15px;
}
</style>

</head>

<body>

<div class="login-container">

    <?php if ($password_message) { ?>
        <div class="password-message"><?= $password_message ?></div>
    <?php } ?>

    <?php if ($error_message) { ?>
        <div class="error-message"><?= $error_message ?></div>
    <?php } ?>

    <img src="hum.png" class="logo">

    <h2>Admin Login</h2>

    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>

    <span class="forgot-link" onclick="document.getElementById('forgot').style.display='block'">
        Forgot Password?
    </span>

    <div class="forgot-container" id="forgot">
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="secret_code" placeholder="Secret Code" required>
            <button type="submit" name="forgot_password">Check</button>
        </form>
    </div>

</div>

</body>
</html>