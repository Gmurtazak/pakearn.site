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
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

$message = '';
$data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        $email = $_POST['email'];

        $email = mysqli_real_escape_string($conn, $email);

        // Fetch ID and join_hoi from records table
        $sql = "SELECT id, join_hoi FROM records WHERE email='$email'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $reff_id = $row['id'];
            $joining_date = $row['join_hoi']; // Use join_hoi now

            // Fetch active users with same reff_id
            $user_sql = "SELECT id, email FROM users WHERE reff_id='$reff_id' AND status='active'";
            $user_result = $conn->query($user_sql);

            if ($user_result && $user_result->num_rows > 0) {
                while ($user_row = $user_result->fetch_assoc()) {
                    $user_id = $user_row['id'];
                    $user_email = $user_row['email'];

                    $amount_sql = "SELECT amount FROM records WHERE id='$user_id'";
                    $amount_result = $conn->query($amount_sql);
                    $amount_row = $amount_result->fetch_assoc();
                    $amount = isset($amount_row['amount']) ? $amount_row['amount'] : 'N/A';

                    $data[] = [
                        'id' => $user_id,
                        'email' => $user_email,
                        'amount' => $amount,
                        'joining_date' => $joining_date
                    ];
                }
            } else {
                $message = "No active records found for the provided email.";
            }
        } else {
            $message = "Email not found in records.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Info</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f0f0f0;
        margin: 0;
        padding: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    .container {
        max-width: 800px;
        width: 100%;
        background-color: #ffffff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        position: relative;
    }

    .container h2 {
        text-align: center;
        color: #8E44AD;
        margin-bottom: 20px;
        font-size: 24px;
        position: sticky;
        top: 0;
        background-color: #ffffff;
        padding: 10px 0;
        z-index: 1;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        margin-bottom: 15px;
    }

    .form-group label {
        font-size: 16px;
        margin-bottom: 5px;
    }

    .form-group input {
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #ccc;
        font-size: 16px;
    }

    .form-group input[type="submit"] {
        background-color: #8E44AD;
        color: #ffffff;
        cursor: pointer;
        border: none;
    }

    .form-group input[type="submit"]:hover {
        background-color: #6C3483;
    }

    .message {
        margin-top: 10px;
        color: red;
    }

    .table-container {
        margin-top: 20px;
        max-height: 300px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid #ccc;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table th, table td {
        padding: 10px;
        border: 1px solid #ccc;
        text-align: left;
    }

    table th {
        background-color: #8E44AD;
        color: #ffffff;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    table td {
        background-color: #f9f9f9;
    }

    .email-cell {
        cursor: pointer;
    }

    .email-cell.full {
        white-space: nowrap;
    }

    @media (max-width: 600px) {
        .container {
            padding: 15px;
        }

        table {
            font-size: 14px;
        }

        .email-cell {
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Information</h2>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Enter Email:</label>
                <input type="email" name="email" id="email" placeholder="Enter Email" required>
            </div>
            <div class="form-group">
                <input type="submit" value="Submit">
            </div>
        </form>

        <?php if ($message) : ?>
        <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>

        <?php if (!empty($data)) : ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Amount</th>
                        <th>Joining Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row) : ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td class="email-cell" onclick="this.classList.toggle('full')">
                            <?php echo $row['email']; ?>
                        </td>
                        <td><?php echo $row['amount']; ?></td>
                        <td><?php echo $row['joining_date']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
    document.querySelectorAll('.email-cell').forEach(cell => {
        cell.addEventListener('click', () => {
            cell.classList.toggle('full');
        });
    });
    </script>
</body>
</html>
