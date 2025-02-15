<?php
session_start();
require_once 'db_connect.php'; // Include your DB connection file

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // ตรวจสอบว่าเชื่อมต่อฐานข้อมูลสำเร็จ
    if ($conn->connect_error) {
        $error = "Connection failed: " . $conn->connect_error;
    } else {
        // Check in users table
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email); // "s" หมายถึง string
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            header('Location: user/a_home.php'); // แก้ไขที่นี่
            exit;
        }

        // Check in technicians table
        $stmt = $conn->prepare("SELECT * FROM technicians WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $technician = $result->fetch_assoc();

        if ($technician && password_verify($password, $technician['password'])) {
            $_SESSION['tech_id'] = $technician['tech_id'];
            header('Location: technician/dashboard.php'); // แก้ไขที่นี่
            exit;
        }

        // Check in admins table
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            header('Location: admin/monitor.php');
            exit;
        }

        // If no match found
        $error = 'Invalid email or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(135deg, #4e73df, #224abe);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        font-family: 'Nunito', sans-serif;
        margin: 0;
    }

    .card {
        width: 100%; /* ปรับขนาดให้เหมาะสมกับหน้าจอเล็ก */
        max-width: 400px; /* จำกัดความกว้างสูงสุด */
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        padding: 1.5rem; /* เพิ่มพื้นที่ภายในการ์ด */
        
    }

    .form-control {
    border-radius: 10px;
    font-size: 1rem;
    width: 100%; /* ให้ฟอร์มขยายเต็มความกว้าง */
}

    .btn-primary {
        background-color: #4e73df;
        border: none;
        border-radius: 50px;
        padding: 10px 20px;
        font-size: 1rem; /* ขนาดฟอนต์ */
    }

    .btn-primary:hover {
        background-color: #224abe;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .alert {
        border-radius: 10px;
        font-size: 0.9rem; /* ขนาดฟอนต์ที่เหมาะสม */
    }

    .btn-google,
    .btn-facebook {
        border-radius: 50px; /* เพิ่มความโค้งมน */
        padding: 10px 20px; /* ขนาด Padding ที่เท่ากัน */
        font-size: 1rem; /* ขนาดฟอนต์ให้เหมาะสม */
        display: flex;
        align-items: center; /* จัดตำแหน่งไอคอนและข้อความ */
        justify-content: center;
    }

    .btn-google {
        background-color: #ea4335;
        color: white;
        border: none;
    }

    .btn-google:hover {
        background-color: #c62828;
    }

    .btn-facebook {
        background-color: #3b5998;
        color: white;
        border: none;
    }

    .btn-facebook:hover {
        background-color: #2d4373;
    }

    /* Media Queries */
    @media (max-width: 576px) {
    .card {
        width: 90%; /* ใช้ 90% เพื่อเว้นขอบซ้ายขวา */
        margin: 10px; /* ทำให้การ์ดอยู่ตรงกลาง */
        padding: 1rem; /* ลด padding สำหรับหน้าจอเล็ก */
    }

    .btn-primary {
        font-size: 1rem; /* ลดขนาดฟอนต์ปุ่ม */
        padding: 10px 20px; /* ปรับขนาด padding ให้เหมาะสม */
    }

    .alert {
        font-size: 0.9rem;
    }
}

 
</style>
</head>
<body>
    <div class="card p-4" style="width: 400px;">
        <div class="text-center mb-4">
            <h1 class="h4 text-gray-900">เข้าสู่ระบบ</h1>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger text-center">
                <?= $error ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="อีเมล" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">เข้าสู่ระบบ</button>
        </form>
        <hr>
        <div class="d-flex flex-column">
            <a href="service_page.php" class="btn btn-google btn-user btn-block mb-2" style="font-size: 0.9rem;">
                <i class="fab fa-google fa-fw"></i> เข้าสู่ระบบด้วย Google
            </a>
            <a href="service_page.php" class="btn btn-facebook btn-user btn-block" style="font-size: 0.9rem;">
                <i class="fab fa-facebook-f fa-fw"></i> เข้าสู่ระบบด้วย Facebook
            </a><br>
            <div class="text-center">
                <a href="register_user.php" class="small">สร้างบัญชีใหม่</a>
            </div>
            <div class="text-center">
                <a href="register_technician.php" class="small">ร่วมงานกับเรา</a>
            </div>
        </div>
    </div>
</body>
</html>

<script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
