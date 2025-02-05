<?php
require_once '../db_connect.php';

// รับข้อมูล JSON จาก JavaScript
$data = json_decode(file_get_contents('php://input'), true);

// ตรวจสอบข้อมูลที่ได้รับ
if (!isset($data['tech_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$tech_id = $data['tech_id'];
$status = $data['status'];

// อัปเดทฟิลด์ status ของ technicians
$query = "UPDATE technicians SET status = ? WHERE tech_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'การเตรียมคำสั่ง SQL ล้มเหลว']);
    exit;
}

$stmt->bind_param("si", $status, $tech_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปเดตสถานะได้']);
}

// ปิดการเชื่อมต่อ
$stmt->close();
$conn->close();
?>
