<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Fetch records based on input email
$email_input = isset($_POST['email']) ? $_POST['email'] : '';
$sql_fetch = "SELECT id, email, password FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Result</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .pending-box {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px auto;
            overflow-x: auto;
        }
        .pending-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .pending-box th, .pending-box td {
            padding: 10px;
            text-align: left;
        }
        .pending-box th {
            background-color: #f2f2f2;
        }
        .pending-box tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .pending-box tr.hidden {
            display: none;
        }
        .button-container {
            text-align: center;
            margin-top: 20px;
        }
        .button-container button {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            margin: 5px;
        }
        .button-container button:hover {
            background-color: #0056b3;
        }
        .form-container {
            text-align: center;
            margin-top: 20px;
        }
        .form-container form {
            display: inline-block;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .form-container input[type="text"] {
            padding: 10px;
            font-size: 16px;
            margin-bottom: 10px;
            width: calc(100% - 22px);
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-container input[type="submit"] {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .form-container input[type="submit"]:hover {
            background-color: #218838;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            h1 {
                font-size: 24px;
            }
            .pending-box {
                padding: 15px;
            }
            .pending-box th, .pending-box td {
                padding: 8px;
                font-size: 14px;
            }
            .button-container button {
                padding: 8px 16px;
                font-size: 14px;
            }
            .form-container input[type="text"] {
                font-size: 14px;
                padding: 8px;
            }
            .form-container input[type="submit"] {
                padding: 8px 16px;
                font-size: 14px;
            }
        }
        @media (max-width: 480px) {
            h1 {
                font-size: 20px;
            }
            .pending-box {
                padding: 10px;
            }
            .pending-box th, .pending-box td {
                padding: 5px;
                font-size: 12px;
            }
            .button-container button {
                padding: 6px 12px;
                font-size: 12px;
            }
            .form-container input[type="text"] {
                font-size: 12px;
                padding: 6px;
            }
            .form-container input[type="submit"] {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
         .login-button {
        display: inline-block;
        padding: 10px 20px;
        background: linear-gradient(to right, #4facfe, #00f2fe);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: bold;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .login-button:hover {
        background: linear-gradient(to right, #00f2fe, #4facfe);
        transform: scale(1.05);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
    }
    </style>
</head>
<body>
    <h1>Search Result</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="email-input" name="email" placeholder="Enter Email" required>
            <input type="submit" value="Search">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Login</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $row_class = 'hidden';
                    if (!empty($email_input) && $row["email"] == $email_input) {
                        $row_class = '';
                    }
                    echo '<tr class="' . $row_class . '">';
                    echo '<td>' . $row["id"] . '</td>';
                    echo '<td>' . $row["email"] . '</td>';
                    echo '<td><a href="https://pakearn.site/smart_login4.php?id=' . $row["id"] . '" class="login-button">Login</a></td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="3">No records found.</td></tr>';
            }
            ?>
        </tbody>

        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <script>
        document.querySelector('form').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission

            var email = document.getElementById('email-input').value;
            toggleRecordDisplay(email);
        });

        function toggleRecordDisplay(email) {
            var tableRows = document.querySelectorAll('#pending-box tbody tr');
            tableRows.forEach(row => {
                if (row.cells[1].textContent.trim() === email.trim()) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function toggleForm() {
            var formContainer = document.getElementById('form-container');
            if (formContainer.style.display === "none" || formContainer.style.display === "") {
                formContainer.style.display = "block";
            } else {
                formContainer.style.display = "none";
            }
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>
