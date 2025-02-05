<?php
require_once '../db_connect.php';

// รับข้อมูลจาก JavaScript
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['tech_id'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$tech_id = $data['tech_id'];

// สร้างคำสั่ง SQL เพื่อดึงข้อมูลงานที่สถานะเป็น 'accepted'
$query = $conn->prepare("
    SELECT sr.request_id, sr.description, sr.status, u.first_name, u.phone
    FROM service_requests sr
    INNER JOIN technicians t ON sr.tech_id = t.tech_id
    INNER JOIN users u ON sr.user_id = u.user_id
    WHERE t.tech_id = ? AND sr.status = 'accepted'
    LIMIT 1
");

// ตรวจสอบการเตรียมคำสั่ง SQL
if (!$query) {
    echo json_encode(['success' => false, 'message' => 'การเตรียมคำสั่ง SQL ล้มเหลว: ' . $conn->error]);
    exit;
}

$query->bind_param("i", $tech_id);
$query->execute();
$result = $query->get_result();
$request = $result->fetch_assoc();

// ส่งข้อมูลกลับไปยัง JavaScript
if ($request) {
    echo json_encode([
        'success' => true,
        'data' => $request
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีงานใหม่ที่สถานะเป็น accepted'
    ]);
}

// ปิดการเชื่อมต่อ
$query->close();
$conn->close();
?>
