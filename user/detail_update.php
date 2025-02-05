<?php
session_start();
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ใช้ request_id จาก session
    $request_id = $_SESSION['request_id'] ?? 0;

    if (!$request_id) {
        echo json_encode(['error' => 'Invalid session request ID']);
        exit;
    }

    $query = "SELECT status, user_id FROM service_requests WHERE request_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if (!$data) {
        echo json_encode(['error' => 'Request not found']);
        exit;
    }

    $status = $data['status'];
    $user_id = $data['user_id'];

    echo $status;

    if ($status === 'completed') {
        // Fetch total_price and tech_id from service_requests
        $query = "SELECT total_price, tech_id FROM service_requests WHERE request_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
    
        $total_price = $row['total_price'];
        $tech_id = $row['tech_id']; // ดึง tech_id จาก service_requests
    
        // Update coin in users table
        $query = "UPDATE users SET coin = coin - ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $total_price, $user_id);
        $stmt->execute();
    
        // Insert payment history
        $query = "INSERT INTO payment_history (user_id, request_id, amount, payment_date) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $user_id, $request_id, $total_price);
        $stmt->execute();
    
        // แบ่งสัดส่วน 85% และ 15%
        $tech_credit = $total_price * 0.85;
        $admin_credit = $total_price * 0.15;
    
        // อัปเดต credit และเพิ่มข้อมูลใน income_history สำหรับ technicians
        $query = "UPDATE technicians SET credit = credit + ? WHERE tech_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("di", $tech_credit, $tech_id);
        $stmt->execute();
    
        // บันทึกข้อมูลใน income_history สำหรับ technicians
        $query = "INSERT INTO income_history (user_type, user_identifier, amount, income_date)
                  VALUES ('technician', ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sd", $tech_id, $tech_credit);
        $stmt->execute();
    
        // อัปเดต request_count และคำนวณ level ใหม่
        $query = "UPDATE technicians
                  SET request_count = request_count + 1,
                      level = CASE
                                 WHEN request_count + 1 BETWEEN 0 AND 15 THEN 'เริ่มต้น'
                                 WHEN request_count + 1 BETWEEN 16 AND 49 THEN 'มืออาชีพ'
                                 WHEN request_count + 1 BETWEEN 50 AND 89 THEN 'ประสบการณ์สูง'
                                 WHEN request_count + 1 BETWEEN 90 AND 199 THEN 'เชี่ยวชาญ'
                                 ELSE 'legendary'
                              END
                  WHERE tech_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $tech_id);
        $stmt->execute();
    
        // อัปเดต credit และเพิ่มข้อมูลใน income_history สำหรับ admins ที่มี status = 'main'
        $query = "SELECT admin_id FROM admins WHERE status = 'main'";
        $result = $conn->query($query);
        if ($result->num_rows > 0) {
            while ($admin = $result->fetch_assoc()) {
                $admin_id = $admin['admin_id'];
    
                // อัปเดต credit
                $query = "UPDATE admins SET credit = credit + ? WHERE admin_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("di", $admin_credit, $admin_id);
                $stmt->execute();
    
                // บันทึกข้อมูลใน income_history
                $query = "INSERT INTO income_history (user_type, user_identifier, amount, income_date)
                          VALUES ('admin', 'main', ?, NOW())";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("d", $admin_credit);
                $stmt->execute();
            }
        }
    }
    
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
