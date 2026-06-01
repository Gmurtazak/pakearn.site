<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the email from the user input
    $email = $_POST['email'];

    // Search for the email in the 'records' table
    $query = "SELECT id FROM records WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch the user's ID
        $row = $result->fetch_assoc();
        $userId = $row['id'];

        // Redirect to user_page.php with the user's ID as a URL parameter (no urlencode needed)
        header("Location: smart_login4.php?id=$userId");
        exit;
    } else {
        // Show an error message if no record is found
        $errorMessage = "No record found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search User</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #d1e8e2, #ffeadb);
        }

        .container {
            width: 100%;
            max-width: 400px;
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.2);
        }

        h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #444;
            margin-bottom: 1.5rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            font-size: 1rem;
            color: #333;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .input-group input[type="email"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .input-group input[type="email"]:focus {
            border-color: #88a2d4;
            outline: none;
            box-shadow: 0 0 8px rgba(136, 162, 212, 0.2);
        }

        .submit-btn {
            width: 100%;
            padding: 0.8rem;
            font-size: 1rem;
            color: #fff;
            background-color: #88a2d4;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #5078a0;
            transform: scale(1.02);
        }

        .error-message {
            margin-top: 1rem;
            color: #e74c3c;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Responsive styling */
        @media (max-width: 480px) {
            .container {
                padding: 1.5rem;
            }

            .input-group label, .submit-btn {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Find User by Email</h2>
    <form method="POST" action="usr.php">
        <div class="input-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="Enter user's email">
        </div>
        <button type="submit" class="submit-btn">Search User</button>
    </form>
    <?php if (!empty($errorMessage)) : ?>
        <div class="error-message"><?= htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>
</div>

</body>
</html>
