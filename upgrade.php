<?php include $_SERVER['DOCUMENT_ROOT'] . '/../private/security.php'; ?>
<?php
// Enhanced security to block SQLi, XSS, and misuse via GET
$blacklist = ['select', 'insert', 'update', 'delete', 'drop', 'truncate', 'alter', '--', ';', '/*', '*/', '@@', '@', 'exec', '1=1'];
$valid_amounts = ['600', '1200', '1800', '3600'];

foreach ($_GET as $param => $value) {
    if (is_array($value)) continue;

    $value = strtolower(trim($value));

    foreach ($blacklist as $word) {
        if (strpos($value, $word) !== false) {
            http_response_code(403);
            exit("Blocked: Suspicious keyword '$word' detected.");
        }
    }

    if (preg_match('/<script|onerror|onload|javascript:/i', $value)) {
        http_response_code(403);
        exit("Blocked: XSS pattern detected.");
    }

    if (strlen($value) > 100 || preg_match('/[^a-z0-9\.\,\-\_\=]/i', $value)) {
        http_response_code(403);
        exit("Blocked: Invalid characters in parameters.");
    }
}

// Optional: Allow only internal access
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if ($referer && stripos($referer, $_SERVER['SERVER_NAME']) === false) {
    http_response_code(403);
    exit("Blocked: External request not allowed.");
}

// Optional: Validate the amount parameter (if accessed directly with it)
if (isset($_GET['amount']) && !in_array($_GET['amount'], $valid_amounts)) {
    http_response_code(403);
    exit("Blocked: Invalid plan amount.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Upgrade Plan</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            text-align: center;
        }

        .container h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
            color: #333;
        }

        .plans {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .plan-box {
            background: #ff5722;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            padding: 20px;
            width: calc(50% - 10px);
            max-width: 280px;
            text-align: center;
            transition: transform 0.3s;
        }

        .plan-box:nth-child(2) {
            background: linear-gradient(135deg, #1E90FF 0%, #00BFFF 100%);
        }

        .plan-box:hover {
            transform: translateY(-10px);
        }

        .plan-box h2 {
            font-size: 1.8em;
            margin: 0 0 10px;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .plan-box p {
            font-size: 1.2em;
            margin: 10px 0;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .buy-button {
            display: inline-block;
            padding: 12px 25px;
            margin-top: 15px;
            background-color: #ffcc00;
            color: #fff;
            text-decoration: none;
            border-radius: 25px;
            font-size: 1em;
            transition: background-color 0.3s ease;
            position: relative;
        }

        .buy-button:hover {
            background-color: #e6b800;
        }

        .buy-button:active {
            background-color: #cc9900;
        }

        .buy-button .icon {
            margin-right: 10px;
        }

        @keyframes button-click {
            0% {
                background-color: #ffcc00;
            }

            50% {
                background-color: #cc9900;
            }

            100% {
                background-color: #ffcc00;
            }
        }

        .buy-button:focus {
            animation: button-click 0.3s ease;
        }

        @media (max-width: 600px) {
            .plan-box {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Buy Plan</h1>
        <div class="plans">
            <div class="plan-box">
                <h2>Basic</h2>
                <p>600.00 Pkr</p>
                <a href="pay.php?amount=600" class="buy-button">
                    <span class="icon">💰</span>Buy on 600
                </a>
            </div>
            <div class="plan-box">
                <h2>Gold</h2>
                <p>1,200.00 Pkr</p>
                <a href="pay.php?amount=1200" class="buy-button">
                    <span class="icon">💰</span>Buy on 1,200
                </a>
            </div>
            <div class="plan-box">
                <h2>Platinum</h2>
                <p>1,800.00 Pkr</p>
                <a href="pay.php?amount=1800" class="buy-button">
                    <span class="icon">💰</span>Buy on 1,800
                </a>
            </div>
            <div class="plan-box">
                <h2>Special</h2>
                <p>3,600.00 Pkr</p>
                <a href="pay.php?amount=3600" class="buy-button">
                    <span class="icon">💰</span>Buy on 3,600
                </a>
            </div>
        </div>
    </div>
</body>

</html>
