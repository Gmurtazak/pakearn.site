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
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php'); // go one folder up
    exit();
}

?>
<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = '';
$email_message = '';
$email = '';
$password = '';

// Check if 'id' is passed in the URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // id ko safe banane ke liye intval use kiya

    // Fetch record from database using id
    $sql = "SELECT email, phone FROM records WHERE id='$id'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row['email'];
        $password = $row['phone'];
    }
}

// Handling POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $email = mysqli_real_escape_string($conn, $email);
        $password = mysqli_real_escape_string($conn, $password);

        $sql = "SELECT * FROM records WHERE email='$email' AND phone='$password'";
        $result = $conn->query($sql);

        if (!$result) {
            die("Query failed: " . $conn->error);
        }

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $name = $row['name'];
            $id = $row['id'];
            $_SESSION['myuser'] = $email;
            $_SESSION['user_id'] = $row['id'];

            if ($row['status'] === 'pending' || $row['status'] === 'rejected') {
                header("Location: status.php?id=$id&name=$name");
                exit();
            } else {
                header("Location: user_page.php?id=$id");
                exit();
            }
        } else {
            $message = "Email or Password not matched. Please try again.";
        }
    } elseif (isset($_POST['forgot_email'])) {
        $forgot_email = $_POST['forgot_email'];
        $forgot_email = mysqli_real_escape_string($conn, $forgot_email);

        $insert_email_sql = "INSERT INTO email_requests (email) VALUES ('$forgot_email')";
        if ($conn->query($insert_email_sql) === TRUE) {
            $email_message = "Your email is received. We will send your password through email in 5h to 10h.";
        } else {
            $email_message = "Error saving email request: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <div class="login-box">
        <a class="download-btn" href="https://pakearn.io/apk/Pak_Earn.apk" download>
            <img src="icon.png" alt="App Icon"> Download App
        </a>
        <h2>Login</h2>
        <form method="POST" action="">
            <input type="email" name="email" id="email" placeholder="Enter Email" value="<?php echo htmlspecialchars($email); ?>" required>
            <div style="position: relative;">
                <input type="password" name="password" id="password" placeholder="Enter Password" value="<?php echo htmlspecialchars($password); ?>" required>
                <i class="fas fa-eye toggle-password" id="togglePassword" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); cursor:pointer;"></i>
            </div>

            <input type="submit" value="Submit">
        </form>

        <?php if ($message) : ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>

        <div class="forgot-password">
            <p>Forgot your password? <a href="javascript:void(0);" onclick="showForgotPassword()">Click here</a></p>
        </div>

        <?php if ($email_message) : ?>
            <div class="email-message"><?php echo $email_message; ?></div>
        <?php endif; ?>

        <div id="forgot-password-form" style="display:none;">
            <form method="POST" action="">
                <input type="email" name="forgot_email" placeholder="Enter your Email" required>
                <input type="submit" value="Submit">
            </form>
        </div>
    </div>

    <script>
        function showForgotPassword() {
            document.getElementById('forgot-password-form').style.display = 'block';
        }

        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>
