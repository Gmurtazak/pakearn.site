<?php
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

date_default_timezone_set('Asia/Karachi');

try {

    // ✅ GET DATA SAFELY
    $id  = isset($_POST['id']) ? (int)$_POST['id'] : 0;
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

    // ✅ GET USER DATA (box + amount)
    $stmt = $conn->prepare("SELECT $box, amount FROM records WHERE id = ?");
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

    // ❌ Already earned check
    if ($row[$box] == 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Already earned'
        ]);
        exit;
    }

    // ✅ LOCK BOX (prevent double click)
    $stmt = $conn->prepare("UPDATE records SET $box = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // 🔥 AMOUNT BASED LOGIC
    $amount = (int)$row['amount'];

    $map = [
        600  => ['base' => 2, 'reward' => 48],
        1200 => ['base' => 3, 'reward' => 97],
        1800 => ['base' => 5, 'reward' => 176],
        3600 => ['base' => 6, 'reward' => 250],
    ];

    if (!isset($map[$amount])) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid amount configuration'
        ]);
        exit;
    }

    $base   = $map[$amount]['base'];
    $reward = $map[$amount]['reward'];

    // 🔥 UPDATE TOTAL EARNINGS
    $conn->query("
        UPDATE earnings 
        SET total_earning = total_earning + $base 
        WHERE id = $id
    ");

    // 🔥 UPDATE LEVEL REWARD
    $conn->query("
        UPDATE level 
        SET reward = reward + $reward 
        WHERE us_id = $id
    ");

    // ✅ RESPONSE
    echo json_encode([
        'success' => true,
        'base' => $base,
        'reward' => $reward,
        'amount' => $amount
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

exit;