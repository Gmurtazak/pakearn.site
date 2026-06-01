<?php
include 'connection.php';

$re_id = $_GET['id'];
$ref_name = "";

// Query to fetch referral person's name
$sql = "SELECT name FROM records WHERE id='$re_id'";
$result = $conn->query($sql);

if ($result === false || $result->num_rows === 0) {
    // Handle case where referral ID is not found or query fails
    $ref_name = "Unknown";
} else {
    $row = $result->fetch_assoc();
    $ref_name = $row['name'];
}

// Handle form submission
$message = "";
$message_phone = "";
$message_email = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
    } else {
        // Check if email already exists
        $check_email_sql = "SELECT * FROM users WHERE email='$email'";
        $check_email_result = $conn->query($check_email_sql);

        if ($check_email_result->num_rows > 0) {
            $message_email = "Email already registered!";
        } else {
            // Check if phone number already exists
            $check_sql = "SELECT * FROM users WHERE ph='$phone'";
            $check_result = $conn->query($check_sql);

            if ($check_result->num_rows > 0) {
                // Phone number already exists
                $message_phone = "Phone number already registered!";
            } else {
                // Start a transaction
                $conn->begin_transaction();

                try {
                    // Insert new user into 'users' table
                    $insert_sql_users = "INSERT INTO users (name, email, ph, password, status, reff_id) 
                                         VALUES ('$name', '$email', '$phone', '$password', 'pending', '$re_id')";
                    $conn->query($insert_sql_users);

                    // Get the last inserted ID
                    $last_id = $conn->insert_id;

                    // Insert new record into 'records' table using the same ID
                    $insert_sql_records = "INSERT INTO records (id, name, email, phone, password, unique_id, status) 
                                           VALUES ('$last_id', '$name', '$email', '$phone', '$password', 0, 'pending')";
                    $conn->query($insert_sql_records);

                    // Commit the transaction
                    $conn->commit();
                    
                    header("Location: upgrade.php");
                    exit();
                } catch (Exception $e) {
                    // Rollback the transaction if an error occurs
                    $conn->rollback();
                    $message = "Error: " . $e->getMessage();
                }
            }
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" type="x-icon" href="hum.png">
    <meta property="og:title" content=Register through this User>
    <meta property="og:site_name" content=Typing Work>
    <meta property="og:url" content=https://typingwork.kesug.com/reg.php?id=5556824&name=Tayyab+Lucky>
    <meta property="og:description" content=Use this link that help you to register easily.>
    <meta property="og:type" content=website>
    <meta property="og:image" content=https://typingwork.kesug.com/hum.png>
    <meta property="og:image:width" content="300">
    <meta property="og:image:height" content="300">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    /* Global styles */
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: Arial, sans-serif;
        background-color: white;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .registration-container {
        background: linear-gradient(35deg, #5a78b7 0%, #e08d9a 100%);
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        width: 100%;
        max-width: 400px;
        padding: 20px;
        text-align: center;
    }

    h2 {
        font-size: 2.5em;
        margin-bottom: 20px;
        color: white;
    }

    p {
        margin-bottom: 10px;
    }

    .referral-info {
        text-align: center;
        margin-bottom: 20px;
        color: #007bff;
        font-size: 18px;
    }

    .message {
        padding: 10px;
        background-color: #dff0d8;
        border: 1px solid #d0e9c6;
        color: #3c763d;
        margin-bottom: 10px;
    }

    form {
        display: flex;
        flex-direction: column;
    }

    .form-group {
        margin-bottom: 15px;
        position: relative;
    }

    label {
        font-weight: bold;
        color: #333;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="tel"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 16px;
        transition: border-color 0.3s ease;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus,
    input[type="tel"]:focus {
        outline: none;
        border-color: #66afe9;
    }

    .error-message {
        color: red;
        font-size: 0.6em;
        margin-top: 5px;
    }

    button {
        color: #fff;
        border: none;
        padding: 12px;
        font-size: 16px;
        margin-bottom: 10px;
        cursor: pointer;
        border-radius: 4px;
                    background-color: green;

        transition: background-color 0.3s ease;
    }

    button:hover {
        background-color: #0056b3;
    }

    .signup {
        margin-top: 20px;
        text-align: center;
    }

    .signup a {
        color: yellow;
        text-decoration: none;
    }

    .signup a:hover {
        text-decoration: underline;
    }

    .toggle-password {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #666;
    }

    .toggle-password:hover {
        color: #333;
    }

    .box {
background: orange;
        /* Purple background */
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        padding: 20px;
        width: 100%;
        text-align: center;
        transition: transform 0.3s;
        color: #fff;
    }

    .box:nth-child(2),
    .box:nth-child(5),
    .box:nth-child(8),
    .box:nth-child(11) {
        background: linear-gradient(135deg, #1E90FF 0%, #00BFFF 100%);
        /* Blue gradient */
    }

    .box:hover {
        transform: translateY(-10px);
    }

    .box h2 {
        font-size: 1.8em;
        margin: 0 0 10px;
        color: white;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
    }

    .box p {
        font-size: 1.2em;
        margin: 10px 0;
        color: white;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
    }

    .box i {
        margin-bottom: 10px;
    }
    .logo {
    width: 180px; /* Adjust size as needed */
    height: 180px;
    display: block;
    margin: 0 auto 20px; /* Center the logo and add space below it */
    margin-right:120px;

}

    @media (max-width: 600px) {
        .box {
            width: 100%;
        }
        .logo{
            margin-right:90px;

        }
    }
     .ad-container iframe {
            max-width: 100%;
            margin-top:20px;
        }
           .ad-container2 iframe {
            max-width: 100%;
            margin-top:0px;
        }
      .ad-container {
    width: 100%;
    text-align: center;
    margin: 5px 0; /* Add equal spacing above and below */
      }
    </style>
</head>

<body>
    <div class="registration-container box">
       
        <h2>Sign Up</h2>
        <p class="referral-info">Referred by: <?php echo $ref_name; ?></p>

        <form action="" method="POST">
            <div class="form-group">
                <input type="text" id="name" name="name" placeholder="Enter your Name" required>
            </div>
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder="Enter your Email" required>
                <?php if (!empty($message_email)): ?>
                <p class="error-message" style="color: #FF7F50;"><?php echo $message_email; ?></p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <input type="tel" id="phone" name="phone" placeholder="Enter your Phone" required>
                <?php if (!empty($message_phone)): ?>
                <p class="error-message" style="color: #FF7F50;"><?php echo $message_phone; ?></p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder="Enter your Password" required>
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
            </div>
            <div class="form-group">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your Password"
                    required>
                <i class="fas fa-eye toggle-password" id="toggleConfirmPassword"></i>
                <?php if (!empty($message)): ?>
                <p class="error-message" style="color: #FF7F50;"><?php echo $message; ?></p>
                <?php endif; ?>
            </div>
            <button type="submit">Submit</button>
            <div class="signup">
                <p>Already Have an Account? <a href="login.php">Sign In</a></p>
                <p><a href="upgrade.php">Pay Fee</a> If You Already Registered?</p>

            </div>
        </form>
    </div>
    <p style="font-size: 10px; color: #bbb;">
  <a href="/blog.php" title="Explore Latest Perbity Blogs Links" style="color: #bbb; text-decoration: none;">c</a>
</p>

    <script>
    // Toggle password visibility script here
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function() {
        // Toggle the type attribute
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);

        // Toggle the eye icon
        this.classList.toggle('fa-eye-slash');
    });

    const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
    const confirmPassword = document.querySelector('#confirm_password');

    toggleConfirmPassword.addEventListener('click', function() {
        // Toggle the type attribute
        const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPassword.setAttribute('type', type);

        // Toggle the eye icon
        this.classList.toggle('fa-eye-slash');
    });
    </script>
</body>

</html>
