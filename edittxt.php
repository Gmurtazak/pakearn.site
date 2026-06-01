<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
$filename = 'move.txt';

// Initialize variables
$text = '';

if (file_exists($filename)) {
    // Read the content from move.txt
    $text = file_get_contents($filename);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["content"])) {
    // Sanitize and write the new content to the file
    $newContent = trim($_POST["content"]);
    if (file_put_contents($filename, $newContent) !== false) {
        $message = "File updated successfully!";
    } else {
        $message = "Failed to update the file.";
    }
    // Reload the page to reflect changes
    header("Location: edittxt.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Move Text</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 90%;
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            margin-top: 0;
            color: #333;
            text-align: center;
        }
        textarea {
            width: 100%;
            height: 300px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
            font-family: 'Arial', sans-serif;
            line-height: 1.5;
            resize: vertical;
        }
        .button-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        button, .go-to-panel-btn {
            background-color: #28a745;
            border: none;
            color: white;
            padding: 10px;
            text-align: center;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        button:hover, .go-to-panel-btn:hover {
            background-color: #218838;
        }
        .go-to-panel-btn {
            background-color: #007bff;
        }
        .go-to-panel-btn:hover {
            background-color: #0056b3;
        }
        .message {
            font-size: 16px;
            color: #28a745;
            text-align: center;
            margin-bottom: 20px;
        }
        @media (max-width: 600px) {
            textarea {
                height: 200px;
            }
            .button-container {
                flex-direction: column;
                align-items: center;
            }
            button, .go-to-panel-btn {
                width: 100%;
                max-width: 300px;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Move Text</h1>
        <?php if (isset($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <textarea name="content"><?php echo htmlspecialchars($text); ?></textarea>
            <div class="button-container">
                <button type="submit">Save Changes</button>
                <a href="panal.php" class="go-to-panel-btn">Go to Panel</a>
            </div>
        </form>
    </div>
</body>
</html>
