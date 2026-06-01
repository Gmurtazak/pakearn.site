<?php include $_SERVER['DOCUMENT_ROOT'] . '/../private/security.php'; ?>
<?php
// Block common SQL injection patterns in GET parameters
function blockMaliciousInput($inputArray) {
    $blacklist = ['select', 'insert', 'update', 'delete', 'drop', 'truncate', 'alter', '--', ';', '/*', '*/', '@@', '@', 'exec', '1=1'];
    foreach ($inputArray as $key => $value) {
        if (is_array($value)) continue;
        $value = strtolower($value);
        foreach ($blacklist as $badWord) {
            if (strpos($value, $badWord) !== false) {
                http_response_code(403);
                exit("Blocked: Malicious input detected in parameter '$key'.");
            }
        }
    }
}
blockMaliciousInput($_GET);
?>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Initialize variables
$name = 'User';
$status = 'pending';
$url = '#';

// Validate ID input
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    echo "Invalid or missing ID in URL.";
    exit();
}

$id = $_GET['id'];

// Use prepared statement to fetch user info securely
$stmt = $conn->prepare("SELECT status, name FROM records WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $status = $data['status'];
    $name = $data['name'];
}
$stmt->close();

// Redirect if status is "active"
if ($status === 'active') {
    header("Location: login.php");
    exit();
}

// Get contact URL securely
$urlStmt = $conn->prepare("SELECT url FROM single_link LIMIT 1");
$urlStmt->execute();
$urlResult = $urlStmt->get_result();
if ($urlResult->num_rows > 0) {
    $urlData = $urlResult->fetch_assoc();
    $url = $urlData['url'];
}
$urlStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activation Status</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { overflow-x: hidden; width: 100%; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f2f5 30%, #dfe4ea 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            max-width: 100vw;
            width: 100%;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h1 {
            color: #ff6b81;
            font-size: 1.8em;
            margin-bottom: 15px;
        }
        p {
            font-size: 1em;
            color: #2f3542;
            margin-bottom: 30px;
            line-height: 1.6;
            text-align: justify;
        }
        .buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .buttons button, .buttons a {
            padding: 12px 18px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            color: white;
            transition: all 0.3s ease-in-out;
        }
        .buttons button {
            background: linear-gradient(90deg, #1e90ff, #6a5acd);
            box-shadow: 0px 4px 12px rgba(30, 144, 255, 0.4);
        }
        .buttons button:hover {
            background: linear-gradient(90deg, #6a5acd, #1e90ff);
            box-shadow: 0px 6px 15px rgba(106, 90, 205, 0.5);
            transform: translateY(-2px);
        }
        .buttons a {
            background: #ff6b81;
        }
        .buttons a:hover {
            background: #ff4757;
        }
        @media (max-width: 600px) {
            .container { width: 100%; padding: 20px; }
            h1 { font-size: 1.5em; }
            p { font-size: 0.95em; }
            .buttons button, .buttons a { font-size: 14px; padding: 10px 16px; }
        }
        body::before, body::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 71, 87, 0.3);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
            z-index: -1;
        }
        body::after {
            background: rgba(30, 144, 255, 0.3);
            animation-direction: reverse;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Activation Status</h1>
        <p>Hello, <?php echo htmlspecialchars($name); ?>! Your request has been received. Our support team will verify your account within 5 minutes to 5 hours. After five minutes, please check your email spam/inbox folder and then try logging in.</p>
        <div class="buttons">
            <button onclick="location.reload()">Refresh</button>
            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank">Contact Us</a>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
