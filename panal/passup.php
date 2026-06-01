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
    header('Location: ../admin_login.php');
    exit();
}

// Verify session security
if (
    $_SESSION['admin']['ip'] !== $_SERVER['REMOTE_ADDR'] ||
    $_SESSION['admin']['user_agent'] !== $_SERVER['HTTP_USER_AGENT']
) {
    session_unset();
    session_destroy();
    header('Location: ../admin_login.php');
    exit();
}

// Session timeout management
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 600)) {
    session_unset();
    session_destroy();
    header('Location: ../admin_login.php');
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
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?><?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['name'];
    $sql_update = "UPDATE records SET name='$new_name' WHERE id='$user_id'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}

// Fetch records based on input ID
$user_id_input = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$sql_fetch = "SELECT id, name, status FROM records";
$result = $conn->query($sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

    .ap {
        color: red;
    }

    .app {
        color: green;
    }

    .pending-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .pending-box th,
    .pending-box td {
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

    .update-form {
        display: none;
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

        .pending-box th,
        .pending-box td {
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

        .pending-box th,
        .pending-box td {
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

    button {
        background-color: black;
        /* Orange background */
        color: white;
        /* White text */
        padding: 8px 14px;
        /* Padding */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Smooth transitions */
        margin: 5px;
        /* Margin */
    }

    button:hover {
        background-color: black;
        /* Darker orange on hover */
        transform: scale(1.05);
        /* Slightly larger on hover */
    }
    </style>
</head>

<body>
    <h1>Password Update</h1>
    <div id="form-container" class="form-container">
        <form method="POST" action="">
            <input type="text" id="user-id-input" name="user_id" placeholder="Enter User ID" required>
            <input type="submit" value="Search User">
        </form>
    </div>
    <div id="pending-box" class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $row_class = 'hidden';
                        if (!empty($user_id_input) && $row["id"] == $user_id_input) {
                            $row_class = '';
                        }
                        echo '<tr class="' . $row_class . '">';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        if($row["status"] === 'pending') {
                            echo '<td class="ap">' . $row["status"] . '</td>';
                        } else {
                            echo '<td class="app">' . $row["status"] . '</td>';
                        }
                        echo '<td><button onclick="openUpdateForm(' . $row["id"] . ', \'' . $row["name"] . '\')">Update Name</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal Page</button>
        </div>
    </div>
    <div id="update-form-container" class="form-container update-form">
        <form method="POST" action="">
            <input type="hidden" id="update-user-id" name="user_id" readonly>
            <input type="text" id="update-user-name" name="name" required>
            <input type="hidden" name="update_name" value="true">
            <input type="submit" value="Update Name">
        </form>
    </div>
    <script>
    document.querySelector('form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var userId = document.getElementById('user-id-input').value;
        toggleRecordDisplay(userId);
    });

    function toggleRecordDisplay(id) {
        var tableRows = document.querySelectorAll('#pending-box tbody tr');
        tableRows.forEach(row => {
            if (row.cells[0].textContent.trim() === id.trim()) {
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

    function openUpdateForm(id, name) {
        document.getElementById('update-user-id').value = id;
        document.getElementById('update-user-name').value = name;
        document.getElementById('update-form-container').style.display = 'block';
    }
    </script>
</body>

</html>

<?php
$conn->close();
?>