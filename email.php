<?php include $_SERVER['DOCUMENT_ROOT'] . '/../private/security.php'; ?>

<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include PHPMailer classes
require 'PHP/PHPMailer.php';
require 'PHP/SMTP.php';
require 'PHP/Exception.php';

// Include the connection file
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Handle email sending when the POST request is made
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];

    // Fetch the user's name and email_sent status from the database
    $query = "SELECT name, email_sent FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    if ($user && !$user['email_sent']) {
        $name = $user['name'];
        $subject = "Account Activated";
        $message = "
            <div style='font-family: Arial, sans-serif;'>
                <h2 style='color: #2c3e50;'>Hi $name,</h2>
                <p>Your account on Pak Earn has been activated successfully. You can now log in to your account and start using our services.</p>
                <p><strong>Please <a href='https://pakearn.io/login.php' style='color: #3498db;'>Click here to Login</a>.</strong></p>
                <br>
                <p>Thank you,<br>Pak Earn Team</p>
            </div>
        ";

        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);

        try {
            // SMTP server configuration
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';        // SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'try786668@gmail.com';    // Admin Gmail address
            $mail->Password   = 'kkxyomlnnyjadoge';      // Admin Gmail app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Sender and recipient details
            $mail->setFrom('try786668@gmail.com', 'Pak Earn');
            $mail->addReplyTo('try786668@gmail.com', 'Pak Earn Support'); // Legitimate reply-to address
            $mail->addAddress($email);  // Email fetched from the record

            // Add additional headers to prevent spam filtering
            $mail->addCustomHeader('X-Mailer', 'PHPMailer');
            $mail->addCustomHeader('List-Unsubscribe', '<mailto:try786668@gmail.com>'); // Adds an unsubscribe option
            $mail->addCustomHeader('X-Priority', '1');  // High priority header

            // Email content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            // Send the email
            $mail->send();

            // Update the database to set email_sent = TRUE
            $updateQuery = "UPDATE users SET email_sent = TRUE WHERE email = '$email'";
            mysqli_query($conn, $updateQuery);

            echo json_encode(["success" => true, "message" => "Email sent successfully to $email"]);
        } catch (Exception $e) {
            // Detailed error output
            echo json_encode(["success" => false, "message" => "Email could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Email has already been sent or user not found"]);
    }
    exit;
}

// Fetch active users from the users table
$sql = "SELECT email, status, email_sent FROM users WHERE status = 'active'";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn)); // Check for SQL errors
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Users</title>
    
    <!-- Style the page -->
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f0f4f8;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 2.5em;
        }

        table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        table, th, td {
            border: 1px solid #e1e1e1;
        }

        th, td {
            padding: 15px;
            text-align: center;
            font-size: 16px;
        }

        th {
            background-color: #f7f7f7;
            color: #333;
        }

        td {
            background-color: #fff;
            color: #555;
        }

        .send-email {
            background-color: #3498db;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .send-email:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }

        .send-email:hover:enabled {
            background-color: #2980b9;
        }

        @media (max-width: 768px) {
            th, td {
                padding: 10px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            th, td {
                padding: 8px;
                font-size: 12px;
            }

            .send-email {
                padding: 8px 15px;
            }
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: #aaa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Active Users</h1>
        <table>
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    // Output data of each row
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . $row['email'] . "</td>";
                        echo "<td>" . $row['status'] . "</td>";
                        $disabled = $row['email_sent'] ? 'disabled' : '';
                        echo "<td><button class='send-email' id='send-email-" . $row['email'] . "' onclick=\"sendEmail('" . $row['email'] . "')\" $disabled>Send Email</button></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No active users found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>&copy; 2024 HappyEarn. All rights reserved.</p>
    </div>

    <!-- JavaScript for sending email -->
    <script>
        function sendEmail(email) {
            // Create AJAX request to handle sending email
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "", true); // Send request to the same file
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function () {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert(response.message);
                        document.getElementById('send-email-' + email).disabled = true; // Disable the button after success
                    } else {
                        alert("Error: " + response.message);
                    }
                } else {
                    alert("An error occurred. Please try again.");
                }
            };
            xhr.send("email=" + encodeURIComponent(email));
        }
    </script>

</body>
</html>
