<?php
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

date_default_timezone_set('Asia/Karachi');

try {

    // ✅ GET DATA SAFELY
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $box = $_POST['box'] ?? '';

    if ($id <= 0 || empty($box)) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing parameters'
        ]);
        exit;
    }

    // ✅ VALID BOX LIST
    $allowed = ['box1','box2','box3','box4','box5'];

    if (!in_array($box, $allowed)) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid box'
        ]);
        exit;
    }

    // ✅ CHECK ALREADY CLICKED
    $stmt = $conn->prepare("SELECT $box FROM records WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if (!$row) {
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        exit;
    }

    if ($row[$box] == 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Already earned'
        ]);
        exit;
    }

    // ✅ UPDATE BOX FIRST (PREVENT DOUBLE CLICK)
    $stmt = $conn->prepare("UPDATE records SET $box = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // 🔥 SIMPLE EARNING (you can later replace with your full logic)
    $earn = 10;

    $conn->query("UPDATE earnings SET total_earning = total_earning + $earn WHERE id = $id");

    $conn->query("UPDATE level SET reward = reward + $earn WHERE us_id = $id");

    // ✅ RESPONSE
    echo json_encode([
        'success' => true,
        'earn' => $earn
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

exit;