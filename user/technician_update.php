<?php
session_start();
require_once '../db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['request_id']) || !isset($data['tech_id'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$request_id = $data['request_id'];
$tech_id = $data['tech_id'];

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล: ' . $conn->connect_error]);
    exit;
}

$conn->begin_transaction();

try {
    $updateRequest = $conn->prepare("
        UPDATE service_requests 
        SET status = 'accepted', tech_id = ? 
        WHERE request_id = ?
    ");
    
    if (!$updateRequest) {
        throw new Exception("เตรียมคำสั่ง SQL สำหรับ updateRequest ไม่สำเร็จ: " . $conn->error);
    }
    
    $updateRequest->bind_param("ii", $tech_id, $request_id);
    $updateRequest->execute();

    if ($updateRequest->affected_rows === 0) {
        throw new Exception("อัปเดต service_requests ไม่สำเร็จ: " . $conn->error);
    }

    $updateTechnician = $conn->prepare("
        UPDATE technicians 
        SET work_status = 'Unavailable' 
        WHERE tech_id = ?
    ");
    
    if (!$updateTechnician) {
        throw new Exception("เตรียมคำสั่ง SQL สำหรับ updateTechnician ไม่สำเร็จ: " . $conn->error);
    }
    
    $updateTechnician->bind_param("i", $tech_id);
    $updateTechnician->execute();

    if ($updateTechnician->affected_rows === 0) {
        throw new Exception("อัปเดต technicians ไม่สำเร็จ: " . $conn->error);
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>
