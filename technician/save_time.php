<?php
require_once '../db_connect.php';

// รับข้อมูล JSON ที่ส่งมาจาก start_job.php
$data = json_decode(file_get_contents('php://input'), true);

// ตรวจสอบข้อมูลที่รับเข้ามา
if (!isset($data['request_id']) || !isset($data['travel_time'])) {
    http_response_code(400);
    echo 'Invalid data provided.';
    exit;
}

$request_id = intval($data['request_id']);
$travel_time = intval($data['travel_time']);

// อัปเดต travel_time และ status ในฐานข้อมูล
$query = $conn->prepare("UPDATE service_requests SET travel_time = ?, status = 'maintain' WHERE request_id = ?");
$query->bind_param("ii", $travel_time, $request_id);
$query->execute();

// ตรวจสอบว่ามีการอัปเดตสำเร็จหรือไม่
if ($query->affected_rows > 0) {
    echo 'Time saved and status updated to "maintain" successfully!';
} else {
    http_response_code(500);
    echo 'Error saving data or no changes made.';
}
?>
