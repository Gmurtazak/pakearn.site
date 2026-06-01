


<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Set timezone to Pakistan
date_default_timezone_set('Asia/Karachi');

// URL se id lo
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Record fetch kro
    $stmt = $conn->prepare("SELECT ads_date FROM records WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($ads_date);
    
    if ($stmt->fetch()) {
        $stmt->close();

        if (!empty($ads_date)) {
            $currentDateTime = date("Y-m-d H:i:s");

            // Compare date/time
            if ($currentDateTime >= $ads_date) {
                // Update record
                $update = $conn->prepare("UPDATE records SET stat = 0, ads_date = NULL, ads_date_start = NULL WHERE id = ?");
                $update->bind_param("i", $id);
                $update->execute();
                $update->close();
            }
        }
    } else {
        $stmt->close();
    }
}

include 'adss.php';

if (isset($_GET['id'])) {
    $us_id = intval($_GET['id']); // URL se id fetch

    // Check if us_id already exists in the level table
    $check = mysqli_query($conn, "SELECT * FROM level WHERE us_id = $us_id");

    if (mysqli_num_rows($check) == 0) {
        // Insert record if not found
        mysqli_query($conn, "INSERT INTO level (us_id, level, reward) VALUES ($us_id, 1, 0)");
    }
}


date_default_timezone_set('Asia/Karachi'); // Set Pakistan timezone
$current_date = date('Y-m-d'); // Get the current Pakistani date

// Fetch ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Check if the record exists
    $query = "SELECT adstop FROM records WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $adstop = $row['adstop'];

        if (is_null($adstop)) {
            // If adstop is NULL, update with current Pakistani date
            $updateQuery = "UPDATE records SET adstop = ? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("si", $current_date, $id);
            $stmt->execute();
            exit(); // Exit after updating
        } elseif ($adstop !== $current_date) {
            // If adstop is not equal to the current date, update box1-box5 and adstop
            $updateQuery = "UPDATE records 
                            SET box1 = 1, box2 = 1, box3 = 1, box4 = 1, box5 = 1, adstop = ? 
                            WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("si", $current_date, $id);
            $stmt->execute();
            echo "<script>location.reload();</script>"; // Refresh page after update
            exit();
        }
    }
}

?>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php'; // Database connection include

// URL se id fetch karna
$id = isset($_GET['id']) ? $_GET['id'] : '';

// Agar id 5557347 ho to protection.php par usi id ke sath redirect kare
if ($id == '5564208' || $id == '5563642') {
    header("Location: protection.php?id=" . $id);
    exit;
}

// Agar id match nahi karti, to kuch nahi hoga

// Rest of your code...
$filename = 'move.txt';
$marqueeText = '';

if (file_exists($filename)) {
    // Read the file content and replace line breaks with spaces
    $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $marqueeText = implode('     ', $lines); // Five spaces as a separator between lines
}



$id = $_GET['id'];
$resultmmm = $conn->query("SELECT box1, box2, box3, box4, box5 FROM records WHERE id = '$id'");
$boxes = $resultmmm->fetch_assoc();
// Query to fetch user details by ID
$sqll = "SELECT * FROM level WHERE us_id='$id'";
$resultt = $conn->query($sqll);
if ($resultt === false || $resultt->num_rows === 0) {
    // Handle case where user ID is not found or query fails
    $level = 0;
    $Reward = 0;
} else 
{
    $roww = $resultt->fetch_assoc();
    $level = $roww['level'];
    $Reward = $roww['reward'];
}
   
$sql = "SELECT * FROM records WHERE id='$id'";
$result = $conn->query($sql);

if ($result === false || $result->num_rows === 0) {
    // Handle case where user ID is not found or query fails
    $name = "Unknown";
} else {
    $row = $result->fetch_assoc();
    $name = $row['name'];

    // Query to fetch user's earnings (assuming 'earnings' table exists with foreign key 'records_id')
    $earningsSql = "SELECT * FROM earnings WHERE id='$id'";
    $earningsResult = $conn->query($earningsSql);

    // Initialize earnings variables
    $referralEarnings = 0.0;
    $adsEarnings = 0.0;
    $totalEarnings = 0.0;

    if ($earningsResult && $earningsResult->num_rows > 0) {
        $earningsRow = $earningsResult->fetch_assoc();
        $referralEarnings = $earningsRow['referral_earning'];
        $adsEarnings = $earningsRow['ads_earning'];
        $totalEarnings = $earningsRow['total_earning'];
    }

  $teamSql = "SELECT users.name, users.email, records.join_hoi
            FROM users
            LEFT JOIN records ON users.id = records.id 
            WHERE users.reff_id='$id' AND users.status='active'";

$teamResult = $conn->query($teamSql);

// Initialize an array to hold team members
$teamMembers = [];
if ($teamResult && $teamResult->num_rows > 0) {
    while ($teamRow = $teamResult->fetch_assoc()) {
        // Use join_hoi field instead of time
        if (!empty($teamRow['join_hoi'])) {
            $formattedTime = new DateTime($teamRow['join_hoi']);
            $formattedDate = $formattedTime->format('d:m:Y');
        } else {
            $formattedDate = null; // or set a default value
        }

        // Add the formatted date to the team member's data
        $teamRow['formatted_time'] = $formattedDate;

        // Add the team member to the array
        $teamMembers[] = $teamRow;
    }
}

    // Query to fetch videos
   // Query to fetch images
 // Query to fetch videos
 $videosSql = "SELECT * FROM videos";
 $videosResult = $conn->query($videosSql);

 // Initialize an array to hold videos
 $videos = [];
 if ($videosResult && $videosResult->num_rows > 0) {
     while ($videoRow = $videosResult->fetch_assoc()) {
         $videos[] = $videoRow;
     }
 }

}
$user_id = $id;

// SQL query likh rahe hain
$sql = "SELECT SUM(amount) AS total_amount FROM withdrawals WHERE user_id = ? AND status = 'active'";

// Prepare statement
if ($stmt = $conn->prepare($sql)) {
    // Bind parameters
    $stmt->bind_param("i", $user_id);
    
    // Execute statement
    $stmt->execute();
    
    // Bind result variables
    $stmt->bind_result($total_amount);
    
    // Fetch value
    $stmt->fetch();
    
    // Print or store the total amount
    
    // Close statement
    $stmt->close();
}
$query = "SELECT url FROM single_link LIMIT 1"; // Assuming there's only one URL
$queryResult = $conn->query($query);

if ($queryResult->num_rows > 0) {
    // Fetch the URL
    $data = $queryResult->fetch_assoc();
    $url = $data['url'];
} else {
    $url = "#"; // Default value if no URL is found
}
$id_to_match = $id; // Replace with the actual ID you want to match

// Query to get the stat value based on the ID
$sql = "SELECT stat FROM records WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_to_match);
$stmt->execute();
$stmt->bind_result($stat_value);
$stmt->fetch();

// Store the stat value in a variable
$stat = $stat_value;
if($stat==0)
{
    header("Location: referral.php?id=$id");
}

// Close the statement and connection
$stmt->close();
$conn->close();
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
            // Split text into individual lines for styling
            $lines = explode('     ', $marqueeText);
            foreach ($lines as $line) {
                echo '<span>' . htmlspecialchars($line) . '</span>';
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
                        <p><?php echo number_format($total_amount); ?> Pkr</p>
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
            <button id="button-<?php echo $i; ?>" class="earn-btn" onclick="handleRedirect(<?php echo $i; ?>)">Click to Earn</button>
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

        // ✅ Immediately disable button to prevent multiple clicks
        button.disabled = true;
        button.innerText = "Please wait...";

        // Update frontend earnings
        var earningsBox = document.querySelector('.earnings-box p');
        var adsEarning = parseFloat(earningsBox.textContent.replace('pkr', '')) + 3;
        earningsBox.textContent = adsEarning.toFixed(2) + ' Pkr';

        // Send AJAX request to update earnings
        fetch('update_earnings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id=${userId}&ads_earning=${adsEarning}&box=box${boxId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.innerText = "Redirected";
                redirectToLink(boxId);
            } else {
                alert(data.error || 'Error updating earnings');
                button.disabled = false;
                button.innerText = "Click to Earn"; // revert
            }
        })
        .catch(error => {
            console.error('Error updating earnings:', error);
            button.disabled = false;
            button.innerText = "Click to Earn"; // revert
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
