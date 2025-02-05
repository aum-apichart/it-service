<?php
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $request_id = $input['request_id'];

    if (isset($request_id)) {
        // กำหนดค่าดีฟอลต์สำหรับฟิลด์
        $default_status = 'pending';
        $default_confirm = 'no';
        $default_tech_id = null;

        // อัปเดตฟิลด์ในฐานข้อมูล
        $stmt = $conn->prepare("UPDATE service_requests SET status = ?, confirm = ?, tech_id = ? WHERE request_id = ?");
        $stmt->bind_param("ssii", $default_status, $default_confirm, $default_tech_id, $request_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request ID']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
