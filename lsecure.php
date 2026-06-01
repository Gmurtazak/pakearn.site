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
include 'session_check.php';
session_start(); // Start the session at the beginning
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$message = '';
$email_message = '';
$email = '';
$password = '';

// Fetch email and password from the URL if available
if (isset($_GET['email']) && isset($_GET['password'])) {
    $email = $_GET['email'];
    $password = $_GET['password'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Sanitize input to prevent SQL injection
        $email = mysqli_real_escape_string($conn, $email);
        $password = mysqli_real_escape_string($conn, $password);

        // Query to fetch user by email and password from records table
        $sql = "SELECT * FROM records WHERE email='$email' AND password='$password'";
        $result = $conn->query($sql);

        if (!$result) {
            die("Query failed: " . $conn->error); // Display detailed error message for debugging
        }

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $name = $row['name']; // Fetching the Name from the fetched record
            $id = $row['id']; // Fetching the ID from the fetched record
            $_SESSION['myuser'] = $email;
            $_SESSION['user_id'] = $row['id']; // Store user ID in session

            if ($row['status'] === 'pending') {
                // Redirect to status page if status is pending
                header("Location: status.php?id=$id&name=$name");
                exit();
            } elseif ($row['status'] === 'rejected') {
                // Redirect to status page if status is rejected
                header("Location: status.php?id=$id&name=$name");
                exit();
            } 
            elseif ($row['status'] === 'active') {
                // Update the user's status in the users table before redirecting to user's personalized page
                $current_time = date('Y-m-d H:i:s'); // Get current time and date
                $status_check_query = "SELECT status FROM users WHERE email='$email'";
                $result = mysqli_query($conn, $status_check_query);
                $row = mysqli_fetch_assoc($result);

                if ($row['status'] !== 'active') 
                {
                    // 2. Update the users table and fetch reff_id
                    $update_sql = "UPDATE users SET status='active' WHERE email='$email'";
                    mysqli_query($conn, $update_sql);
                    $update_sqll = "UPDATE records SET  time='$current_time' WHERE email='$email'";
                    mysqli_query($conn, $update_sqll);
                    // 3. Fetch the reff_id based on the email
                    $reff_id_query = "SELECT reff_id FROM users WHERE email='$email'";
                    $result = mysqli_query($conn, $reff_id_query);
                    $row = mysqli_fetch_assoc($result);
                    $reff_id = $row['reff_id'];

                    // 4. Fetch the amount from the records table using reff_id
                    $amount_query = "SELECT amount FROM records WHERE id='$id'";
                    $result = mysqli_query($conn, $amount_query);
                    $row = mysqli_fetch_assoc($result);
                    $amount = $row['amount'];

                    // 5. Fetch referral_earning_start and reward_bonus_start from referral_values table
                    $referral_query = "SELECT referral_earning_start, reward_bonus_start 
                       FROM referral_values 
                       WHERE price='$amount'";
                    $result = mysqli_query($conn, $referral_query);
                    $row = mysqli_fetch_assoc($result);
                    $referral_earning_start = $row['referral_earning_start'];
                    $reward_bonus_start = $row['reward_bonus_start'];

                    // 6. Default $level to 1
                    $level = 1;

                    // 7. Fetch the level from the level table by matching reff_id with us_id
                    $level_query = "SELECT level FROM level WHERE us_id='$reff_id'";
                    $result = mysqli_query($conn, $level_query);

                    // 8. If a record is found, set $level to the value from the table
                    if (mysqli_num_rows($result) > 0)
                    {
                        $row = mysqli_fetch_assoc($result);
                        $level = $row['level'];
                    }

                    // 9. Loop through each level to calculate referral earnings and reward bonuses
                    $referral_earning = 0;
                    $reward_bonus = 0;

                    for ($i = 1; $i <= $level; $i++) 
                    {
                        $referral_earning = $referral_earning_start + ($i - 1);
                        $reward_bonus = $reward_bonus_start + ($i - 1);
                    }

                    // 10. Update earnings table, adding $referral_earning to both referral_earning and total_earning
                    $update_earnings_query = "UPDATE earnings 
                              SET referral_earning = referral_earning + '$referral_earning', 
                                  total_earning = total_earning + '$referral_earning'
                              WHERE id='$reff_id'";
                    mysqli_query($conn, $update_earnings_query);

                    // 11. Update the reward in the level table, adding $reward_bonus to the reward column
                    $update_level_query = "UPDATE level 
                           SET reward = reward + '$reward_bonus' 
                           WHERE us_id='$reff_id'";
                    mysqli_query($conn, $update_level_query);

                    // 12. Fetch and count all records from the users table where reff_id is $reff_id and status is 'active'
                    $active_users_query = "SELECT COUNT(*) AS active_count 
                           FROM users 
                           WHERE reff_id='$reff_id' 
                           AND status='active'";
                    $result = mysqli_query($conn, $active_users_query);
                    $row = mysqli_fetch_assoc($result);
                    $active_count = $row['active_count'];

                    // 13. Determine new level based on active_count
                    if ($active_count >= 410) {
                        $new_level = 10;
                    }elseif ($active_count >=290) {
                        $new_level = 9;
                    }elseif ($active_count >= 215) {
                        $new_level = 8;
                    }elseif ($active_count >= 140) {
                        $new_level = 7;
                    } elseif ($active_count >= 90) {
                        $new_level = 6;
                    } elseif ($active_count >= 45) {
                        $new_level = 5;
                    } elseif ($active_count >= 20) {
                        $new_level = 4;
                    } elseif ($active_count >= 10) {
                        $new_level = 3;
                    } elseif ($active_count >= 5) {
                        $new_level = 2;
                    } else {
                        $new_level = 1;
                    }

                    // 14. Update the level in the level table
                    $update_new_level_query = "UPDATE level 
                               SET level = '$new_level' 
                               WHERE us_id = '$reff_id'";
                     mysqli_query($conn, $update_new_level_query);
                    if ($conn->query($update_sql) === TRUE) 
                    {
                    // Insert a record into the earnings table
                        $insert_earning_sql = "INSERT INTO earnings (id, total_earning, referral_earning, ads_earning) VALUES ('$id', 0, 0, 0)";
                        if ($conn->query($insert_earning_sql) === TRUE) 
                        {
                        // Insert a record into the level table
                            $insert_level_sql = "INSERT INTO level (us_id, level, reward) VALUES ('$id', '1', 0)";
                            if ($conn->query($insert_level_sql) === TRUE) 
                            {
                                $sql_timer = "UPDATE records SET timer = 0 WHERE id = '$reff_id'";
                                if ($conn->query($sql_timer) === TRUE)
                                {
                                   // Step 1: Get price from plan table
                                    $planSql = "SELECT price FROM plan WHERE user_id = '$id'";
                                    $planResult = mysqli_query($conn, $planSql);
                                    $price = 0;
                                    
                                    if (mysqli_num_rows($planResult) > 0) {
                                        $planRow = mysqli_fetch_assoc($planResult);
                                        $price = $planRow['price'];
                                    }
                                    
                                    // Step 2: Get commissions from indirect tables
                                    $comm = 0;
                                    $comm2 = 0;
                                    
                                    if ($price > 0) {  // Only proceed if we found a valid price
                                        // First commission level
                                        $commSql = "SELECT comm FROM indirect WHERE price = '$price'";
                                        $commResult = mysqli_query($conn, $commSql);
                                        if (mysqli_num_rows($commResult) > 0) {
                                            $commRow = mysqli_fetch_assoc($commResult);
                                            $comm = $commRow['comm'];
                                        }
                                    
                                        // Second commission level
                                        $commSql2 = "SELECT comm2 FROM indirect2 WHERE price2 = '$price'";
                                        $commResult2 = mysqli_query($conn, $commSql2);
                                        if (mysqli_num_rows($commResult2) > 0) {
                                            $commRow2 = mysqli_fetch_assoc($commResult2);
                                            $comm2 = $commRow2['comm2'];
                                        }
                                    }
                                    
                                    // Step 3: Get referral hierarchy
                                    $reff_id1 = '';
                                    $reff_id2 = '';
                                    $reff_id3 = '';
                                    
                                    // First level referral
                                    $userSql1 = "SELECT reff_id FROM users WHERE id = '$id'";
                                    $userResult1 = mysqli_query($conn, $userSql1);
                                    if (mysqli_num_rows($userResult1) > 0) {
                                        $userRow1 = mysqli_fetch_assoc($userResult1);
                                        $reff_id1 = $userRow1['reff_id'];
                                        
                                        // Second level referral (only if first level exists)
                                        if (!empty($reff_id1)) {
                                            $userSql2 = "SELECT reff_id FROM users WHERE id = '$reff_id1'";
                                            $userResult2 = mysqli_query($conn, $userSql2);
                                            if (mysqli_num_rows($userResult2) > 0) {
                                                $userRow2 = mysqli_fetch_assoc($userResult2);
                                                $reff_id2 = $userRow2['reff_id'];
                                                
                                                // Third level referral (only if second level exists)
                                                if (!empty($reff_id2)) {
                                                    $userSql3 = "SELECT reff_id FROM users WHERE id = '$reff_id2'";
                                                    $userResult3 = mysqli_query($conn, $userSql3);
                                                    if (mysqli_num_rows($userResult3) > 0) {
                                                        $userRow3 = mysqli_fetch_assoc($userResult3);
                                                        $reff_id3 = $userRow3['reff_id'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    // Step 4: Update earnings
                                    // For second level referral (gets $comm)
                                    if (!empty($reff_id2) && $comm > 0) {
                                        $updateSql = "UPDATE earnings 
                                                      SET referral_earning = referral_earning + $comm,
                                                          total_earning = total_earning + $comm
                                                      WHERE id = '$reff_id2'";
                                        mysqli_query($conn, $updateSql);
                                    }
                                    
                                    // For third level referral (gets $comm2)
                                    if (!empty($reff_id3) && $comm2 > 0) {
                                        $updateSql = "UPDATE earnings 
                                                      SET referral_earning = referral_earning + $comm2,
                                                          total_earning = total_earning + $comm2
                                                      WHERE id = '$reff_id3'";
                                        mysqli_query($conn, $updateSql);
                                    }
                                    

                                    // Redirect to user's personalized page if status is active
                                    header("Location: user_page.php?id=$id");
                                    exit();
                                }
                               else 
                                {
                                    $message = "Error updating timer record: " . $conn->error;
                                }
                            }
                        else 
                        {
                            $message = "Error inserting level record: " . $conn->error;
                        }
                    }
                    else
                    {
                        $message = "Error inserting earning record: " . $conn->error;
                    }
                } 
                else 
                {
                    $message = "Error updating status: " . $conn->error;
                }
                    // Now you have $new_level updated in the level table
                } 
                else 
                {
                    // Status is already 'active', no need to execute the queries again
                    echo "User's status is already active.";
                     // Redirect to user's personalized page if status is active
                    header("Location: user_page.php?id=$id");
                    exit();

                }
                
            }
        } 
        else 
        {
            $message = "Email or Password not matched. Please try again.";
        }
    } 
    elseif (isset($_POST['forgot_email'])) 
    {
        $forgot_email = $_POST['forgot_email'];

        // Sanitize input to prevent SQL injection
        $forgot_email = mysqli_real_escape_string($conn, $forgot_email);

        // Insert the email into email_requests table
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-box {
            background: #8E44AD;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
            text-align: center;
            color: #fff;
        }

        .login-box h2 {
            font-size: 2em;
            margin-bottom: 20px;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .login-box input[type="email"],
        .login-box input[type="password"],
        .login-box input[type="submit"] {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border: 1px solid #ccc;
            width: calc(100% - 22px);
            box-sizing: border-box;
        }

        .login-box input[type="email"],
        .login-box input[type="password"] {
            background-color: #fff;
            color: #333;
        }

        .login-box input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
            border: none;
        }

        .login-box input[type="submit"]:hover {
            background-color: #e6b800;
        }

        .login-box input[type="submit"]:active {
            background-color: #cc9900;
        }

        .message {
            margin-top: 10px;
            color: red;
        }

        .email-message {
            margin-top: 10px;
            color: #fff;
            background-color: #28a745;
            padding: 15px;
            border-radius: 8px;
            font-size: 14px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .signup {
            margin-top: 20px;
            font-size: 14px;
            color: #fff;
        }

        .signup a {
            color: #ffcc00;
            text-decoration: none;
        }

        .signup a:hover {
            text-decoration: underline;
        }

        .forgot-password {
            margin-top: 10px;
            font-size: 14px;
            color: #fff;
            text-align: center;
        }

        .forgot-password a {
            color: #ffcc00;
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .toggle-password {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .toggle-password:hover {
            color: #333;
        }
          .download-btn {
            background: green;
            color: #fff;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 15px;
            transition: all 0.3s ease;
            font-weight: bold;
            font-size: 16px;
        }

        .download-btn:hover {
            background: linear-gradient(135deg, #e67e22, #d35400);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .download-btn img {
            width: 30px;
            height: 30px;
            margin-right: 10px;
        }

        /* Responsive */
        @media screen and (max-width: 768px) {
            .login-box {
                padding: 20px;
            }

            .download-btn {
                padding: 10px 20px;
            }

            .download-btn img {
                width: 25px;
                height: 25px;
            }
        }
    </style>
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
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
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
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>
