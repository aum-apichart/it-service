<?php
session_start();
require_once '../db_connect.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

if (isset($_SESSION['tech_id'])) {
    $tech_id = $_SESSION['tech_id'];

    // อัปเดตสถานะเป็น offline
    $stmt = $conn->prepare("UPDATE technicians SET status = 'offline' WHERE tech_id = ?");
    $stmt->bind_param("i", $tech_id);
    $stmt->execute();
}

// ล้างเซสชันและเปลี่ยนหน้าไปที่ login
session_destroy();
header("Location: ../login.php");
exit();
?>
