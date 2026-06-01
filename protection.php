<?php include $_SERVER['DOCUMENT_ROOT'] . '/../private/security.php'; ?>
<?php
// Block common SQL injection attempts via GET parameters (case-insensitive)
$blacklist = ['select', 'insert', 'update', 'delete', 'drop', 'truncate', 'alter', '--', ';', '/*', '*/', '@@', '@', 'exec', '1=1'];

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
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php'; // Including your existing database connection file
include 'session_check.php';
// Fetch ID from URL
$id = isset($_GET['id']) ? $_GET['id'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advance Protection</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(to right, #1e3c72, #2a5298);
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 350px;
        }
        .container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .input-box {
            position: relative;
            margin-bottom: 20px;
        }
        .input-box input {
            width: 100%;
            padding: 10px;
            font-size: 18px;
            text-align: center;
            border: 2px solid #2a5298;
            border-radius: 5px;
            outline: none;
        }
        .input-box i {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #2a5298;
        }
        .btn {
            width: 100%;
            padding: 10px;
            font-size: 18px;
            background: #2a5298;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn:hover {
            background: #1e3c72;
        }
        .error {
            color: red;
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-lock"></i> Advance Protection</h2>
        <form method="POST">
            <div class="input-box">
                <input type="password" name="pincode" placeholder="Enter 6-digit PIN" maxlength="6" required>
                <i class="fas fa-key"></i>
            </div>
            <button type="submit" class="btn">Submit</button>
        </form>
        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $pincode = $_POST['pincode'];
            if ($pincode == '786786') {
                echo "<script>window.location.href='mysweet.php?id=$id';</script>";
            } else {
                echo "<p class='error'>Your pin code is wrong!</p>";
            }
        }
        ?>
    </div>
</body>
</html>
