<?php
// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// รับข้อมูลจาก JavaScript
$data = json_decode(file_get_contents('php://input'), true);

// ตรวจสอบว่าได้รับข้อมูลครบถ้วนหรือไม่
if (
    !isset($data['tech_id']) ||
    !isset($data['latitude']) ||
    !isset($data['longitude']) ||
    !isset($data['status']) ||
    !isset($data['work_status'])
) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

// ค่า variables
$tech_id = $data['tech_id'];
$latitude = $data['latitude'];
$longitude = $data['longitude'];
$status = $data['status'];
$work_status = $data['work_status'];

// ตรวจสอบค่า latitude และ longitude ให้อยู่ในช่วงที่ถูกต้อง
if (!is_numeric($latitude) || !is_numeric($longitude)) {
    echo json_encode(['success' => false, 'message' => 'ค่าพิกัดไม่ถูกต้อง']);
    exit;
}

// สร้างคำสั่ง SQL สำหรับอัปเดตสถานะ
$query = "UPDATE technicians SET latitude = ?, longitude = ?, status = ?, work_status = ? WHERE tech_id = ?";
$stmt = $conn->prepare($query);

// ตรวจสอบการเตรียม statement
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'การเตรียมคำสั่งล้มเหลว: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ddssi", $latitude, $longitude, $status, $work_status, $tech_id);

// Execute query และตรวจสอบผล
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'อัปเดตสถานะสำเร็จ']);
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปเดตสถานะได้: ' . $stmt->error]);
}

// ปิดการเชื่อมต่อ
$stmt->close();
$conn->close();
?>
