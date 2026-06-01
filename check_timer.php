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
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

$reff_id = $_GET['id'];

// Directly fetch the timer value from the week table
$sql_check_timer = "SELECT timer FROM week WHERE user_id = ?";
$stmt = $conn->prepare($sql_check_timer);
$stmt->bind_param("i", $reff_id);
$stmt->execute();
$result = $stmt->get_result();

$response = array('timer_expired' => false);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['timer'] == 0) {
        $response['timer_expired'] = true;
    }
}

echo json_encode($response);

$stmt->close();
$conn->close();
?>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

$reff_id = $_GET['id'];

// Directly fetch the timer value from the week table
$sql_check_timer = "SELECT timer FROM week WHERE user_id = ?";
$stmt = $conn->prepare($sql_check_timer);
$stmt->bind_param("i", $reff_id);
$stmt->execute();
$result = $stmt->get_result();

$response = array('timer_expired' => false);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['timer'] == 0) {
        $response['timer_expired'] = true;
    }
}

echo json_encode($response);

$stmt->close();
$conn->close();
?>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

$reff_id = $_GET['id'];

// Directly fetch the timer value from the week table
$sql_check_timer = "SELECT timer FROM week WHERE user_id = ?";
$stmt = $conn->prepare($sql_check_timer);
$stmt->bind_param("i", $reff_id);
$stmt->execute();
$result = $stmt->get_result();

$response = array('timer_expired' => false);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['timer'] == 0) {
        $response['timer_expired'] = true;
    }
}

echo json_encode($response);

$stmt->close();
$conn->close();
?>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

$reff_id = $_GET['id'];

// Directly fetch the timer value from the week table
$sql_check_timer = "SELECT timer FROM week WHERE user_id = ?";
$stmt = $conn->prepare($sql_check_timer);
$stmt->bind_param("i", $reff_id);
$stmt->execute();
$result = $stmt->get_result();

$response = array('timer_expired' => false);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['timer'] == 0) {
        $response['timer_expired'] = true;
    }
}

echo json_encode($response);

$stmt->close();
$conn->close();
?>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

$reff_id = $_GET['id'];

// Directly fetch the timer value from the week table
$sql_check_timer = "SELECT timer FROM week WHERE user_id = ?";
$stmt = $conn->prepare($sql_check_timer);
$stmt->bind_param("i", $reff_id);
$stmt->execute();
$result = $stmt->get_result();

$response = array('timer_expired' => false);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['timer'] == 0) {
        $response['timer_expired'] = true;
    }
}

echo json_encode($response);

$stmt->close();
$conn->close();
?>
