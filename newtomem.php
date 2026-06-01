<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Enable error reporting for debugging


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
                <p><strong>Please <a href='https://pakearn.site/login.php' style='color: #3498db;'>Click here to Login</a>.</strong></p>
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

// Handle hide functionality
if (isset($_GET['hide']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $updateQuery = "UPDATE records SET newapprove = 1 WHERE id = '$id'";
    mysqli_query($conn, $updateQuery);
    echo json_encode(["success" => true, "message" => "Record hidden successfully"]);
    exit;
}

// Function to get user details (id, reff_id, password)
function getUserDetails($email, $conn) {
    $query = "SELECT id, reff_id, ph FROM users WHERE email = '$email'";
    $res = $conn->query($query);
    return $res->fetch_assoc();
}

// Check if the status check is triggered
if (isset($_GET['checkStatus']) && isset($_GET['id'])) {
    $id = $_GET['id'];

    // Query to check the user's status based on the provided id
    $query = "SELECT status FROM users WHERE id = '$id'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo $row['status']; // Echo the status (active/pending/etc.)
    } else {
        echo 'error'; // If no result, return error
    }
    exit(); // Prevent further processing of the script
}

$sql = "SELECT email, name, status FROM records WHERE status = 'active' AND newapprove = 0";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Approved</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
        }
        body {
            font-size: 12px;
        }
        h1 {
            text-align: center;
            color: #333;
            font-size: 18px;
            margin-top: 10px;
        }
        .pending-box {
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 100%;
            height: calc(100vh - 20px);
            overflow: auto;
            margin: 0 auto;
            box-sizing: border-box;
        }
        .pending-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .pending-box th, .pending-box td {
            padding: 2px 4px;
            text-align: left;
            white-space: nowrap;
            border: 1px solid #ddd;
        }
        .pending-box th {
            background-color: #f2f2f2;
        }
        .pending-box tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .pending-box td.email,
        .pending-box td.name,
        .pending-box td.password {
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            word-break: break-all;
        }
        .button-container {
            text-align: center;
            margin-top: 10px;
        }
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-top: 10px;
        }
        .action-buttons button {
            background-color: #007BFF;
            color: white;
            padding: 10px 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s ease;
        }
        .khud-btn {
            background-color: #6f42c1;
        }
        .khud-btn:hover {
            background-color: #563d7c;
        }
        .lead-btn {
            background-color: #e83e8c;
        }
        .lead-btn:hover {
            background-color: #d6336c;
        }
        .email-btn {
            background-color: #28a745;
        }
        .email-btn:hover {
            background-color: #218838;
        }
        .hide-btn {
            background-color: #dc3545;
        }
        .hide-btn:hover {
            background-color: #c82333;
        }

        @media (max-width: 768px) {
            .pending-box {
                padding: 5px;
            }
            .pending-box table {
                font-size: 10px;
            }
            .pending-box th, .pending-box td {
                padding: 1px 2px;
            }
            .pending-box td.email,
            .pending-box td.name,
            .pending-box td.password {
                max-width: 80px;
            }
            .action-buttons button {
                font-size: 10px;
                padding: 8px 5px;
            }
        }

        @media (max-width: 480px) {
            .pending-box {
                height: calc(100vh - 20px);
            }
            .pending-box table {
                font-size: 9px;
            }
            .pending-box th, .pending-box td {
                padding: 1px 1px;
            }
            .pending-box td.email,
            .pending-box td.name,
            .pending-box td.password {
                max-width: 60px;
            }
            .action-buttons {
                gap: 3px;
            }
            .action-buttons button {
                font-size: 9px;
                padding: 8px 0px;
            }
        }
    </style>
</head>
<body>
    <h1>Member Approved</h1>
    <div class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Password</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Get user id, reff_id, and password from users table
                        $userDetails = getUserDetails($row["email"], $conn);
                        $id = $userDetails['id'];
                        $reff_id = $userDetails['reff_id'];
                        $password = $userDetails['ph'];

                        echo '<tr>';
                        echo '<td class="email">' . $row["email"] . '</td>';
                        echo '<td class="name">' . $row["name"] . '</td>';
                        echo '<td class="password">' . $password . '</td>';
                        echo '<td>' . $row["status"] . '</td>';
                        echo '<td class="action-buttons">
                                <button class="khud-btn" onclick="checkStatusAndRedirect(\'' . $id . '\', \'' . $row["email"] . '\', \'' . $password . '\')">Khud</button>
                                <button class="lead-btn" onclick="window.location.href=\'https://pakearn.site/smart_login4.php?id=' . $reff_id . '\'">Lead</button>
                                <button class="email-btn" onclick="sendEmail(\'' . $row["email"] . '\')">Send Email</button>
                                <button class="hide-btn" onclick="hideRecord(\'' . $id . '\')">Hide</button>
                              </td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5">No pending records.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <script>
        function checkStatusAndRedirect(id, email, password) {
            fetch('?checkStatus=true&id=' + id)
                .then(response => response.text())
                .then(status => {
                    if (status.includes('active')) {
                        window.location.href = 'https://pakearn.site/smart_login4.php?id=' + id;
                    } else if (status.includes('pending')) {
                        window.location.href = 'https://pakearn.site/login2.php?email=' + encodeURIComponent(email) + '&password=' + encodeURIComponent(password);
                    } else {
                        alert('Error: Could not determine user status.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

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

        function hideRecord(id) {
            // Create AJAX request to handle hiding the record
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "?hide=true&id=" + id, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert(response.message);
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        alert("Error: " + response.message);
                    }
                } else {
                    alert("An error occurred. Please try again.");
                }
            };
            xhr.send();
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>