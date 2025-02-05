<?php
session_start();
require_once '../db_connect.php';

// ตรวจสอบว่า user login หรือไม่
if (!isset($_SESSION['tech_id'])) {
    die("Unauthorized access.");
}

$tech_id = intval($_SESSION['tech_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason']);
    $issue = trim($_POST['issue']);

    if (empty($reason) || empty($issue)) {
        die("Please fill in all required fields.");
    }

    // ดึง request ล่าสุดของ technician
    $query = $conn->prepare("SELECT request_id FROM service_requests WHERE tech_id = ? AND status = 'accepted' ORDER BY created_at DESC LIMIT 1");
    $query->bind_param("i", $tech_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 0) {
        die("No active requests found to cancel.");
    }

    $service_request = $result->fetch_assoc();
    $service_request_id = $service_request['request_id'];

    // เปลี่ยนสถานะในตาราง service_requests เป็น 'cancelled'
    $updateQuery = $conn->prepare("UPDATE service_requests SET status = 'cancelled' WHERE request_id = ?");
    $updateQuery->bind_param("i", $service_request_id);
    if (!$updateQuery->execute()) {
        die("Failed to update request status: " . $conn->error);
    }

    $stmt = $conn->prepare("UPDATE technicians SET work_status = 'Available' WHERE tech_id = (SELECT tech_id FROM service_requests WHERE request_id = ?)");
    $stmt->bind_param("i", $request_id);

    if (!$stmt->execute()) {
        throw new Exception("Error updating technicians status: " . $stmt->error);
    }


    // บันทึกข้อมูลลงในตาราง job_cancel
    $insertQuery = $conn->prepare("INSERT INTO job_cancel (request_id, tech_id, cancel_reason, issue_type) VALUES (?, ?, ?, ?)");
    $insertQuery->bind_param("iiss", $service_request_id, $tech_id, $reason, $issue);
    if (!$insertQuery->execute()) {
        die("Failed to log cancellation: " . $conn->error);
    }

    // หลังจากดำเนินการสำเร็จ เปลี่ยนเส้นทางไปที่ dashboard.php
    header("Location: dashboard.php");
    exit();
} else {
    die("Invalid request method.");
}
?>
