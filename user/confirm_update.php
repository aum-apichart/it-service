<?php
session_start();
require_once '../db_connect.php';

// รับ JSON จากคำขอ
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['request_id'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่มี request_id']);
    exit;
}

$request_id = $data['request_id'];

// อัปเดตฟิลด์ confirm ใน service_requests
$updateQuery = $conn->prepare("UPDATE service_requests SET confirm = 'yes' WHERE request_id = ?");
$updateQuery->bind_param("i", $request_id);

if ($updateQuery->execute()) {
    // ตรวจสอบว่ามีช่างที่สถานะออนไลน์หรือไม่
    $checkStatusQuery = $conn->prepare("SELECT COUNT(*) AS online_count FROM technicians WHERE status = 'online'");
    $checkStatusQuery->execute();
    $statusResult = $checkStatusQuery->get_result();
    $onlineCount = $statusResult->fetch_assoc()['online_count'];

    if ($onlineCount > 0) {
        // หากมีช่างออนไลน์ ส่งข้อมูลเพื่อเปลี่ยนเส้นทางไป technicians_online.php
        echo json_encode(['success' => true, 'redirect' => 'technicians_online.php']);
    } else {
        // หากไม่มีช่างออนไลน์ ส่งข้อมูลเพื่อเปลี่ยนเส้นทางไป wait.php
        echo json_encode(['success' => true, 'redirect' => 'wait.php']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปเดตข้อมูลได้']);
}
