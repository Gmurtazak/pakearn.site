<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Set timezone once for the entire script
date_default_timezone_set('Asia/Karachi');
$current_date = date('Y-m-d');
$current_datetime = date("Y-m-d H:i:s");

// Fetch and sanitize ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    // Handle invalid ID case if necessary
    die("Invalid User ID");
}

// 1. Initial Protection / Redirection check
if ($id == 5564208 || $id == 5563642) {
    header("Location: protection.php?id=" . $id);
    exit;
}

include 'adss.php';

// Load marquee text from move.txt
$marqueeText = file_exists('move.txt') ? file_get_contents('move.txt') : "Welcome to Pak Earn! Please add members to increase your earnings.";

// 2. Initial Records Data Fetch & Expiration Logic
// We fetch everything from records first to minimize queries
$stmt = $conn->prepare("SELECT * FROM records WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$recordsResult = $stmt->get_result();
$recordsRow = $recordsResult->fetch_assoc();
$stmt->close();

if (!$recordsRow) {
    die("User record not found");
}

// Check expiration of ads_date
if (!empty($recordsRow['ads_date']) && $current_datetime >= $recordsRow['ads_date']) {
    $update = $conn->prepare("UPDATE records SET stat = 0, ads_date = NULL, ads_date_start = NULL WHERE id = ?");
    $update->bind_param("i", $id);
    $update->execute();
    $update->close();
    // Update local data to reflect change
    $recordsRow['stat'] = 0;
    $recordsRow['ads_date'] = NULL;
}

// 3. Adstop / Daily Box Logic
if (is_null($recordsRow['adstop'])) {
    $update = $conn->prepare("UPDATE records SET adstop = ? WHERE id = ?");
    $update->bind_param("si", $current_date, $id);
    $update->execute();
    $update->close();
    // No reload needed for NULL -> Today, but let's follow original logic if it exit()
    exit(); 
} elseif ($recordsRow['adstop'] !== $current_date) {
    $update = $conn->prepare("UPDATE records SET box1 = 1, box2 = 1, box3 = 1, box4 = 1, box5 = 1, adstop = ? WHERE id = ?");
    $update->bind_param("si", $current_date, $id);
    $update->execute();
    $update->close();
    echo "<script>location.reload();</script>";
    exit();
}

// 4. Level Initialization Check
$stmt = $conn->prepare("SELECT * FROM level WHERE us_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$levelResult = $stmt->get_result();
$levelRow = $levelResult->fetch_assoc();
$stmt->close();

if (!$levelRow) {
    $stmt = $conn->prepare("INSERT INTO level (us_id, level, reward) VALUES (?, 1, 0)");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $level = 1;
    $Reward = 0;
} else {
    $level = $levelRow['level'];
    $Reward = $levelRow['reward'];
}

// 5. Fetch Additional Data (Earnings, Team, Withdrawals, Videos, Single Link)

// Earnings
$stmt = $conn->prepare("SELECT * FROM earnings WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$earningsResult = $stmt->get_result();
$earningsRow = $earningsResult->fetch_assoc();
$stmt->close();

$referralEarnings = $earningsRow['referral_earning'] ?? 0.0;
$adsEarnings = $earningsRow['ads_earning'] ?? 0.0;
$totalEarnings = $earningsRow['total_earning'] ?? 0.0;

// Team Members
$teamMembers = [];
$teamSql = "SELECT users.name, users.email, records.join_hoi
            FROM users
            LEFT JOIN records ON users.id = records.id 
            WHERE users.reff_id = ? AND users.status = 'active'";
$stmt = $conn->prepare($teamSql);
$stmt->bind_param("i", $id);
$stmt->execute();
$teamResult = $stmt->get_result();
while ($teamRow = $teamResult->fetch_assoc()) {
    if (!empty($teamRow['join_hoi'])) {
        $formattedTime = new DateTime($teamRow['join_hoi']);
        $teamRow['formatted_time'] = $formattedTime->format('d:m:Y');
    } else {
        $teamRow['formatted_time'] = null;
    }
    $teamMembers[] = $teamRow;
}
$stmt->close();

// Videos
$videos = [];
$videosResult = $conn->query("SELECT * FROM videos");
if ($videosResult) {
    while ($videoRow = $videosResult->fetch_assoc()) {
        $videos[] = $videoRow;
    }
}

// Withdrawals (active status sum)
$total_withdrawal_amount = 0;
$stmt = $conn->prepare("SELECT SUM(amount) AS total_amount FROM withdrawals WHERE user_id = ? AND status = 'active'");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($total_withdrawal_amount);
$stmt->fetch();
$stmt->close();

// Single Link
$url = "#";
$linkResult = $conn->query("SELECT url FROM single_link LIMIT 1");
if ($linkResult && $linkRow = $linkResult->fetch_assoc()) {
    $url = $linkRow['url'];
}

// Data for display blocks (legacy compatibility if needed)
$name = $recordsRow['name'];
$boxes = [
    'box1' => $recordsRow['box1'],
    'box2' => $recordsRow['box2'],
    'box3' => $recordsRow['box3'],
    'box4' => $recordsRow['box4'],
    'box5' => $recordsRow['box5'],
];

// 6. Final Redirection Check (if stat is 0)
if ($recordsRow['stat'] == 0) {
    header("Location: referral.php?id=$id");
    exit();
}

// Final cleanup and close
$conn->close();

// Rest of the HTML/Output logic would go here if there was any...
// Note: The original file ended after the stat check and connection close.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $name; ?>'s Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">

</head>
<style>
   .ad-container iframe {
            max-width: 100%;
            margin-top:20px;
        }
           .ad-container2 iframe {
            max-width: 100%;
            margin-top:0px;
        }
        h5
        {
            text-align:center;
            color:green;
        }
      .ad-container {
    width: 100%;
    text-align: center;
    margin: 5px 0; /* Add equal spacing above and below */
      }
      .marquee-container {
          background: #000;
          color: #fff;
          padding: 5px 0;
          overflow: hidden;
          width: 100%;
          margin-bottom: 10px;
          display: flex;
      }
      .marquee-content {
          display: inline-block;
          white-space: nowrap;
          animation: marquee 25s linear infinite;
          padding-left: 0;
          font-weight: bold;
      }
      .marquee-content span {
          padding-right: 40px;
      }
      @keyframes marquee {
          0% { transform: translateX(0); }
          100% { transform: translateX(-50%); }
      }
</style>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h3>User Dashboard</h3>
        </div>
        <ul class="sidebar-menu">

            <li><a href="withdraw.php?id=<?php echo $id; ?>"><i class="fas fa-money-check-alt"></i> Withdraw</a></li>
            <li><a href="wihis.php?id=<?php echo $id; ?>"><i class="fas fa-history"></i> Withdraw History</a></li>
            <li><a href="chpass.php?id=<?php echo $id; ?>"><i class="fas fa-lock"></i> Change Password</a></li>

            <li><a href="reflink.php?id=<?php echo $id; ?>"><i class="fas fa-share-alt"></i> Referral Link</a></li>
            <li><a href="<?php echo htmlspecialchars($url); ?>"><i class="fas fa-cog"></i>Join Our Group</a>
            <li><a href="login.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>

            </li>
        </ul>
    </div>
    <div class="navbar">
        <div class="title">Pak Earn</div>
        <div class="menu-icon" onclick="window.location.href='profile.php?id=<?php echo $id; ?>';"><i class="fas fa-bars"></i></div>
    </div>

    <!-- Sidebar menu for mobile -->
    <ul class="sidebar-menu-mobile" id="mobileMenu">

        <li><a href="withdraw.php?id=<?php echo $id; ?>"><i class="fas fa-money-check-alt"></i> Withdraw</a></li>
        <li><a href="wihis.php?id=<?php echo $id; ?>"><i class="fas fa-history"></i> Withdraw History</a></li>
        <li><a href="chpass.php?id=<?php echo $id; ?>"><i class="fas fa-lock"></i> Change Password</a></li>

        <li><a href="reflink.php?id=<?php echo $id; ?>"><i class="fas fa-share-alt"></i> Referral Link</a></li>
        <li><a href="<?php echo htmlspecialchars($url); ?>"><i class="fas fa-cog"></i>Join Our Group</a></li>
        <li><a href="login.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>


    </ul>
    <div class="content">
        <div class="user-box">
          
            <div class="marquee-container">
                <div class="marquee-content">
                    <?php 
                    // Split text into lines and render twice for seamless looping
                    for($i = 0; $i < 2; $i++) {
                        // Split by any newline character
                        $lines = preg_split('/\r\n|\r|\n/', $marqueeText);
                        foreach ($lines as $line) {
                            $trimmedLine = trim($line);
                            if ($trimmedLine !== '') {
                                echo '<span>' . htmlspecialchars($trimmedLine) . '</span>';
                            }
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="user-info-box">
                <h2>Pak Earn</h2>

            </div>
            <div class="additional-boxes">
                <div class="additional-box active " onclick="window.location.href='leve.php?id=<?php echo $id; ?>';">
                    Level
                </div>
               <div class="additional-box " onclick="window.location.href='profile.php?id=<?php echo $id; ?>';">
                    My Profile

                </div>
            </div>
            <div class="earnings">
                <div class="earnings-boxes">
                    <div class="earnings-box" onclick="window.location.href='withdraw.php?id=<?php echo $id; ?>';">
                        <h4>Current Balance</h4>
                        <p><?php echo number_format($totalEarnings); ?> Pkr</p>
                    </div>
                    <div class="earnings-box bx"onclick="window.location.href='rewar.php?id=<?php echo $id; ?>';">
                        <h4>Total Rewards</h4>
                        <p><?php echo number_format($Reward); ?> Pkr</p>
                    </div>
                   
                    <div class="earnings-box" onclick="window.location.href='wihis.php?id=<?php echo $id; ?>';">
                        <h4>Total Withdraw</h4>
                        <p><?php echo number_format($total_withdrawal_amount); ?> Pkr</p>
                    </div>
                    
                
                </div>
            </div>
            <div class="additional-boxes">
                <div class="additional-box active" id="additional-box" onclick="toggleSection('ads')">
                    Earn from AD's
                </div>
                <div class="additional-box " id="additional-box" onclick="toggleSection('team')">
                    My Team
                </div>
                
            </div>

           <div id="ads-section" class="additional-section active">
    <h3>AD's Section</h3>
    <div class="buttons-container">
    <?php for ($i = 1; $i <= 5; $i++): ?>
        <div class="button-wrapper">
            <button 
                id="button-<?php echo $i; ?>" 
                class="earn-btn"
                onclick="handleRedirect(<?php echo $i; ?>)"
            >
                Click to Earn
            </button>
        </div>
    <?php endfor; ?>
</div>
</div>

<div id="team-section" class="additional-section">
    <h3>Team Section</h3>
    <?php if (count($teamMembers) > 0): ?>
    <div class="team-members">
        <?php foreach ($teamMembers as $member): ?>
        <div class="team-member">
            <p><strong>Name: </strong><?php echo $member['name']; ?></p>
            <p><strong>Email: </strong><?php echo $member['email']; ?></p>
            <p><strong>Date Joined: </strong><?php echo $member['formatted_time']; ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p>No active team members found.</p>
    <?php endif; ?>
</div>

<div class="whatsapp-icon" onclick="window.location.href='<?php echo htmlspecialchars($url); ?>'">
    <i class="fab fa-whatsapp"></i>
</div>


</div> <!-- ending main container -->

</div> <!-- ending outer container -->
<p style="font-size: 10px; color: #bbb;">
  <a href="/blog.php" title="Explore Latest Perbity Blogs Links" style="color: #bbb; text-decoration: none;">c</a>
</p>
<script>
    setTimeout(function(){
        window.location.reload(true);
    }, 15000);

    function toggleSection(section) {
        var teamSection = document.getElementById('team-section');
        var adsSection = document.getElementById('ads-section');
        var teamButton = document.querySelector('#additional-box:nth-child(2)');
        var adsButton = document.querySelector('#additional-box:nth-child(1)');

        if (section === 'team') {
            teamSection.classList.add('active');
            adsSection.classList.remove('active');
            teamButton.classList.add('active');
            adsButton.classList.remove('active');
        } else if (section === 'ads') {
            adsSection.classList.add('active');
            teamSection.classList.remove('active');
            adsButton.classList.add('active');
            teamButton.classList.remove('active');
        }
    }

    function redirectToLevel1() {
        window.location.href = 'level1.php?id=<?php echo $id; ?>';
    }

    var boxStatus = <?php echo json_encode($boxes); ?>;
    var userId = "<?php echo $id; ?>";

    document.addEventListener("DOMContentLoaded", function() {
        for (var i = 1; i <= 5; i++) {
            var button = document.getElementById('button-' + i);
            if (boxStatus['box' + i] == 0) {
                button.disabled = true;
                button.innerText = "Visit at 12 AM";
            }
        }
    });

    function handleRedirect(boxId) {

    var button = document.getElementById('button-' + boxId);

    if (button.disabled) return;

    button.disabled = true;
    var originalText = button.innerText;
    button.innerText = "Please wait...";

    fetch("update_earnings.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
            id: userId,   // 🔴 replace this with actual user id variable
            box: 'box' + boxId
        })
    })
    .then(response => response.json())
    .then(data => {

        console.log(data);

        if (data.success) {
            button.innerText = "Redirected";
            redirectToLink(boxId);
        } else {

            alert(data.error || 'Error updating earnings');

            if (data.error && data.error.includes("Already earned")) {
                button.innerText = "Visit at 12 AM";
                button.disabled = true;
            } else {
                button.disabled = false;
                button.innerText = originalText;
            }
        }

    })
    .catch(error => {
        console.error('Error:', error);
        button.disabled = false;
        button.innerText = originalText;
    });
}
    function redirectToLink(boxId) {
        fetch(`get_link.php?id=${boxId}`)
            .then(response => response.json())
            .then(data => {
                if (data.link_url) {
                    window.location.href = data.link_url;
                } else {
                    alert(data.error || 'Invalid ID');
                }
            })
            .catch(error => console.error('Error fetching link:', error));
    }

    function toggleMobileMenu() {
        var mobileMenu = document.getElementById('mobileMenu');
        mobileMenu.style.display = (mobileMenu.style.display === 'block') ? 'none' : 'block';
    }

    function downloadImage(filePath, fileName) {
        var a = document.createElement('a');
        a.href = filePath;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
</script>

</body>
</html>
