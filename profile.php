<?php include $_SERVER['DOCUMENT_ROOT'] . '/../private/security.php'; ?>
<?php
include 'session_check.php';
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

// Include connection.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Fetch WhatsApp link from contacts table
$whatsapp_link = 'https://chat.whatsapp.com/default-link'; // Default fallback
$query = "SELECT whatsapp_link FROM contacts WHERE id = 1";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $whatsapp_link = $row['whatsapp_link'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 20px;
            user-select:none;
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

        .main-box {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            padding: 20px;
            width: 90%;
        }

        .welcome {
            font-size: 2.5em;
            margin-bottom: 20px;
            font-weight:bold;
            color: #333;
        }

        .grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .box {
            background: #ff5722;
            /* Purple background */
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            padding: 20px;
            width: calc(50% - 10px);
            max-width: 280px;
            text-align: center;
            transition: transform 0.3s;
            color: #fff;
        }

        .box:hover {
            transform: translateY(-10px);
        }

        .box h2 {
            font-size: 1.8em;
            margin: 0 0 10px;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .box p {
            font-size: 1.5em;
            margin: 10px 0;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .box i {
            margin-bottom: 20px;
            color: yellow;
        }

        @media (max-width: 600px) {
            .box {
                width: 30%;
            }

            .box h2 {
                font-size: 1.3em;
                margin: 0 0 10px;
                color: #fff;
                text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            }
        }
         .ad-container iframe {
            max-width: 100%;
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

        /* Enhanced Banner Ad Styles - More Lovely & Eye-Catching */
        .banner-ad {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 40%, #fad0c4 100%);
            border-radius: 20px;
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.3);
            padding: 15px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            max-width: 100%;
            animation: gentleFloat 3s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }

        .banner-ad::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: rotate(45deg);
            animation: shimmer 2s linear infinite;
        }

        .banner-ad i.whatsapp-icon {
            font-size: 2.5em;
            color: #25D366; /* WhatsApp green */
            animation: heartbeat 1.5s ease-in-out infinite;
            z-index: 1;
        }

        .banner-ad i.heart-icon {
            font-size: 1.8em;
            color: #ff6b6b;
            animation: heartbeat 2s ease-in-out infinite reverse;
            z-index: 1;
        }

        .banner-text {
            font-size: 1.4em;
            font-weight: bold;
            color: #333;
            margin: 0;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.5);
            z-index: 1;
        }

        .join-btn {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .join-btn::after {
            content: '❤️';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            transition: transform 0.3s ease;
        }

        .join-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.6);
            background: linear-gradient(135deg, #128C7E 0%, #25D366 100%);
        }

        .join-btn:hover::after {
            transform: translate(-50%, -50%) scale(1);
        }

        @keyframes gentleFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        @keyframes heartbeat {
            0% { transform: scale(1); }
            14% { transform: scale(1.3); }
            28% { transform: scale(1); }
            42% { transform: scale(1.3); }
            70% { transform: scale(1); }
        }

        @media (max-width: 768px) {
            .banner-ad {
                flex-direction: column;
                gap: 10px;
                padding: 12px;
                margin: 15px 0;
            }
            .banner-text {
                font-size: 1.2em;
            }
            .banner-ad i.whatsapp-icon {
                font-size: 2em;
            }
            .join-btn {
                padding: 10px 20px;
                font-size: 1em;
            }
        }

        @media (max-width: 480px) {
            .banner-text {
                font-size: 1.1em;
            }
            .banner-ad i.whatsapp-icon {
                font-size: 1.8em;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="main-box">
       
            <div class="welcome">My Profile</div>

            <!-- Enhanced Lovely Banner Ad - Inside Main Box -->
            <div class="banner-ad">
                <i class="fab fa-whatsapp whatsapp-icon"></i>
                <p class="banner-text">Pak Earn Training Channel</p>
                <a href="<?php echo htmlspecialchars($whatsapp_link); ?>" target="_blank" class="join-btn">Join Now</a>
            </div>

            <div class="grid">
                <div class="box" id="pdata">
                    <i class="fas fa-user-circle fa-3x"></i> <!-- Changed icon -->
                    <h2>My Info</h2>
                </div>
                <div class="box" id="referralLinkBox">
                    <i class="fas fa-user fa-3x"></i>
                    <h2>Referral Link</h2>
                </div>
                <div class="box" id="withdrawBox">
                    <i class="fas fa-money-bill-wave fa-3x"></i>
                    <h2>Withdraw</h2>
                </div>
                <div class="box" id="withdrawHistoryBox">
                    <i class="fas fa-money-check-alt fa-3x"></i>
                    <h2>Withdraw History</h2>
                </div>
                
                <div class="box" id="updatePasswordBox">
                    <i class="fas fa-key fa-3x"></i>
                    <h2>Update Password</h2>
                </div>
                
                <div class="box" id="logoutBox">
                    <i class="fas fa-sign-out-alt fa-3x"></i>
                    <h2>Logout</h2>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Function to get the query parameters from the URL
        function getQueryParams() {
            let params = {};
            let queryString = window.location.search.substring(1);
            let regex = /([^&=]+)=([^&]*)/g;
            let match;
            while (match = regex.exec(queryString)) {
                params[decodeURIComponent(match[1])] = decodeURIComponent(match[2]);
            }
            return params;
        }

        // Fetch the ID from the URL
        let queryParams = getQueryParams();
        let userId = queryParams.id;

        // Redirect functions
         document.getElementById('pdata').onclick = function() {
            window.location.href = 'pdata.php?id=' + userId;
        };
        document.getElementById('withdrawBox').onclick = function() {
            window.location.href = 'withdraw.php?id=' + userId;
        };

        document.getElementById('withdrawHistoryBox').onclick = function() {
            window.location.href = 'wihis.php?id=' + userId;
        };
      
        document.getElementById('updatePasswordBox').onclick = function() {
            window.location.href = 'chpass.php?id=' + userId;
        };

        document.getElementById('referralLinkBox').onclick = function() {
            window.location.href = 'reflink.php?id=' + userId;
        };

        document.getElementById('logoutBox').onclick = function() {
            window.location.href = 'logout.php';
        };
    </script>
</body>

</html>