<?php
require '../db_connect.php'; // เชื่อมต่อฐานข้อมูล

session_start();
$user_id = $_SESSION['user_id']; // สมมติว่ามีการล็อกอินอยู่และเก็บ user_id ไว้ใน session

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_type_id = $_POST['service_type_id'];
    $description = $_POST['description'];
    $distance = $_POST['distance'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $total_price = $_POST['total_price'];

    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($service_type_id) || empty($distance) || empty($latitude) || empty($longitude) || empty($total_price)) {
        echo "กรุณากรอกข้อมูลให้ครบถ้วน";
        exit;
    }

    // อัปโหลดไฟล์รูปภาพ
    $uploadedImages = [];
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            $fileName = basename($_FILES['images']['name'][$key]);
            $targetPath = "uploads/" . $fileName;

            if (move_uploaded_file($tmpName, $targetPath)) {
                $uploadedImages[] = $fileName;
            }
        }
    }

    $imagesJson = json_encode($uploadedImages);

    // SQL สำหรับการบันทึกข้อมูล
    $stmt = $conn->prepare("INSERT INTO service_requests (user_id, service_type_id, description, distance, latitude, longitude, total_price, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisdssds", $user_id, $service_type_id, $description, $distance, $latitude, $longitude, $total_price, $imagesJson);

    if ($stmt->execute()) {
        echo "บันทึกคำขอเรียบร้อยแล้ว!";
    } else {
        echo "เกิดข้อผิดพลาด: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
