<?php include $_SERVER['DOCUMENT_ROOT'] . '/../private/security.php'; ?>
<?php
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
?>
<?php
// Database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

$id = $_GET['id']; // Get the unique ID from the request

if (empty($id)) {
    die("No unique ID provided.");
}

// Query to find the last record in the users table with the specified reff_id
$sql_last_user = "SELECT id FROM users WHERE reff_id = ? ORDER BY id DESC LIMIT 1";
$stmt_last_user = $conn->prepare($sql_last_user);
$stmt_last_user->bind_param("s", $id);
$stmt_last_user->execute();
$result_last_user = $stmt_last_user->get_result();

if ($result_last_user->num_rows > 0) {
    $row_last_user = $result_last_user->fetch_assoc();
    $last_user_id = $row_last_user['id'];
    
    // Retrieve the price from the plan table for the last user's ID
    $sql_price = "SELECT price FROM plan WHERE user_id = ?";
    $stmt_price = $conn->prepare($sql_price);
    $stmt_price->bind_param("i", $last_user_id);
    $stmt_price->execute();
    $result_price = $stmt_price->get_result();

    if ($result_price->num_rows > 0) {
        $row_price = $result_price->fetch_assoc();
        $price = $row_price['price'];

        // Fetch referral earning and reward bonus dynamically from the referral_values table
        $sql_values = "SELECT referral_earning_start, reward_bonus_start FROM referral_values WHERE price = ?";
        $stmt_values = $conn->prepare($sql_values);
        $stmt_values->bind_param("i", $price);
        $stmt_values->execute();
        $result_values = $stmt_values->get_result();

        if ($result_values->num_rows > 0) {
            $row_values = $result_values->fetch_assoc();
            $referral_earning_start = $row_values['referral_earning_start'];
            $reward_bonus_start = $row_values['reward_bonus_start'];

            // Query to find active records associated with the unique ID
            $sql = "SELECT id, reff_id FROM users WHERE reff_id = ? AND status = 'active'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            $active_count = 0; // Initialize the active count

            if ($result->num_rows > 0) {
                // Process each active record
                while ($row = $result->fetch_assoc()) {
                    $user_id = $row['id'];
                    $reff_id = $row['reff_id'];

                    // Insert or update the ref table
                    $sql_ref = "INSERT INTO ref (user_id, ref_add) VALUES (?, true) ON DUPLICATE KEY UPDATE ref_add = true";
                    $stmt_ref = $conn->prepare($sql_ref);
                    $stmt_ref->bind_param("i", $user_id);
                    $stmt_ref->execute();

                    if ($stmt_ref->affected_rows > 0) {
                        // Check the current level of the referrer
                        $sql_level = "SELECT level FROM level WHERE us_id = ?";
                        $stmt_level = $conn->prepare($sql_level);
                        $stmt_level->bind_param("i", $reff_id);
                        $stmt_level->execute();
                        $result_level = $stmt_level->get_result();
                        $level = 1; // Default level

                        if ($result_level->num_rows > 0) {
                            $row_level = $result_level->fetch_assoc();
                            $level = intval($row_level['level']);
                        }

                        // Loop through each level to calculate referral earnings and reward bonuses
                        $referral_earning = 0;
                        $reward_bonus = 0;

                        for ($i = 1; $i <= $level; $i++) {
                            $referral_earning = $referral_earning_start + ($i - 1);
                            $reward_bonus = $reward_bonus_start + ($i - 1);
                        }

                        // Update the referral_earning and total_earning for the reff_id in the earnings table
                        $sql_earnings = "UPDATE earnings SET referral_earning = referral_earning + ?, total_earning = total_earning + ? WHERE id = ?";
                        $stmt_earnings = $conn->prepare($sql_earnings);
                        $stmt_earnings->bind_param("iii", $referral_earning, $referral_earning, $reff_id);
                        $stmt_earnings->execute();

                        // Update the reward in the level table
                        $sql_reward = "UPDATE level SET reward = reward + ? WHERE us_id = ?";
                        $stmt_reward = $conn->prepare($sql_reward);
                        $stmt_reward->bind_param("ii", $reward_bonus, $reff_id);
                        $stmt_reward->execute();
                    }
                    $active_count++; // Increment the active count
                }
            }

            // Update level based on the active count
            if ($active_count >= 250) {
                $new_level = 9;
            } elseif ($active_count >= 230) {
                $new_level = 8;
            } elseif ($active_count >= 190) {
                $new_level = 7;
            } elseif ($active_count >= 140) {
                $new_level = 6;
            } elseif ($active_count >= 80) {
                $new_level = 5;
            } elseif ($active_count >= 45) {
                $new_level = 4;
            } elseif ($active_count >= 20) {
                $new_level = 3;
            } elseif ($active_count >= 5) {
                $new_level = 2;
            } else {
                $new_level = 1;
            }

            if (isset($new_level)) {
                $sql_new_level = "UPDATE level SET level = ? WHERE us_id = ?";
                $stmt_new_level = $conn->prepare($sql_new_level);
                $stmt_new_level->bind_param("ii", $new_level, $id);
                $stmt_new_level->execute();
            }

        } else {
            die("No values found for the price.");
        }

    } else {
        die("No price found for the user.");
    }
} else {
    die("No users found with the given reff_id.");
}

$stmt_last_user->close();
$conn->close();
?>
