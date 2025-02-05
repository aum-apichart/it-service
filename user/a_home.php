<?php
session_start();
require_once '../db_connect.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่า session สำหรับผู้ใช้มีข้อมูลหรือไม่
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// ดึงข้อมูลจากตาราง users โดยใช้ user_id
$sql = "SELECT first_name, coin FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$first_name = "";
$coin = 0;

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $first_name = $row['first_name'];
    $coin = $row['coin'];
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT SERVICE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Kanit', sans-serif;
        }

        body {
            margin: 0;
            /* ลบ margin เริ่มต้น */
            padding-bottom: 80px;
            /* เพิ่มพื้นที่ด้านล่างเท่ากับความสูงของ footer */
            box-sizing: border-box;
            /* เพื่อให้ padding ถูกนับรวมในขนาดของ body */
        }

        header {
            background-color: #007BFF;
            color: white;
            padding: 15px;
            text-align: center;
        }

        .navbar {
            background-color: #0795ff;
        }

        .navbar-nav .nav-item .nav-link {
            color: white;
            padding-right: 15px;
        }

        .navbar-nav .nav-item .nav-link:hover {
            color: #f8f9fa;
        }

        .navbar-brand {
            color: white;
            font-weight: bold;
        }

        .navbar-toggler {
            border: none;
            /* ลบกรอบ */
            outline: none;
            /* ปิดกรอบเมื่อคลิก */
        }

        .navbar-toggler:focus {
            outline: none;
            /* ปิดกรอบโฟกัส */
            box-shadow: none;
            /* ลบเอฟเฟกต์โฟกัส */
        }

        .wallet-icon {
            width: 38px;
            height: 38px;
            object-fit: cover;
        }

        .footer-icon {
            width: 38px;
            height: 38px;
            object-fit: cover;
            margin-right: 0;
            /* ลบการเว้นระยะเพิ่มเติม */
        }

        .navbar-toggler {
            margin-left: 0;
            /* ชิดกับไอคอนระฆัง */
        }

        .section-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            /* ระยะห่างระหว่างข้อความกับเส้น */
        }

        .section-title::before,
        .section-title::after {
            content: "";
            flex: 1;
            height: 2px;
            /* ความหนาของเส้น */
            background-color: #DCDCDC;
            /* สีของเส้น */
        }

        .section-title h5 {
            margin: 0;
            color: #BEBEBE;
            /* สีของข้อความ */
        }

        .btn-primary {
            width: 100%;
            max-width: 95%;
            border-radius: 25px;
            padding: 15px 0;
            font-size: 1.2em;
        }

        .carousel-inner img {
            width: 100%;
            height: auto;
            border-radius: 15px;
        }

        footer {
            background-color: #f8f9fa;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            left: 0;
            /* ทำให้ชิดซ้ายสุด */
            width: 100vw;
            /* กว้างเต็มจอ */
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
            margin: 0;
            /* ลบ margin */
        }

        .card-title {
            font-size: 1.25rem;
            color: black;
        }

        p.price {
            font-size: 1.15rem;
            /* ปรับขนาดให้ใหญ่ขึ้น */
            font-weight: bold;
            color: orange;
        }


        .card {
            border-radius: 20px;
            /* เพิ่มความโค้งมนให้กับขอบ */
        }

        .balance-container {
            background-color: rgb(252, 249, 249);
            /* พื้นหลังสีเทาอ่อน */

            border-radius: 22px;
            /* มุมโค้ง */
            padding: 10px 15px;
            /* พื้นที่ภายใน */
            margin: 5px 0;
            /* ระยะห่าง */
        }

        .balance-text {
            color: black;
            /* สีดำสำหรับ "ยอดคงเหลือ" และ "บาท" */
            font-size: 1rem;
            /* ขนาดตัวอักษรปกติ */
            font-weight: normal;
            /* ตัวอักษรไม่หนา */
        }

        .coin-text {
            font-size: 1.1rem;
            /* ขนาดใหญ่สำหรับตัวเลข */
            color: #00CC00;
            /* สีเขียวสำหรับยอดเงิน */
            font-weight: bold;
            /* ตัวหนา */
        }

        .balance-container a {
            text-decoration: none;
            /* ลบเส้นใต้ลิงก์ */
        }


        @media (max-width: 576px) {
            .card-title {
                font-size: 1rem;
            }

            .price {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <div style="font-size: 1.5rem; font-weight: bold; margin-left: 1px;">
                <img src="icon/logo2.png" alt="IT Service Logo" style="width: 140px; height: auto;">
            </div>
            <div class="d-flex align-items-center">
                <!-- ไอคอนระฆัง -->
                <a href="notification.php">
                    <img src="icon/bell1.png" alt="Notifications" class="footer-icon">
                </a>
                <!-- ปุ่ม Navbar Toggler -->
                <button class="navbar-toggler ms-1" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item d-flex align-items-center balance-container">
                        <a href="profile.php" class="nav-link ms-3">
                            <img src="icon/profile.png" alt="Profile" class="footer-icon">
                        </a>
                        <a href="topup.php" class="nav-link d-flex align-items-center">
                            <span class="ms-2">
                                <span class="balance-text">ยอดคงเหลือ</span>
                                <span class="coin-text">
                                    <?php echo number_format($coin, 2, '.', ','); ?>
                                </span>
                                <span class="balance-text"></span>
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>



    <!-- Content Section -->
    <div class="container text-center mt-3">
        <a href="service_page.php" class="btn btn-primary">แจ้งงานซ่อม กดที่นี่</a>
    </div>

    <div class="container mt-4">
        <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="img_ad/ad1.png" class="d-block w-100" alt="Ad 1">
                </div>
                <div class="carousel-item">
                    <img src="img_ad/ad2.png" class="d-block w-100" alt="Ad 2">
                </div>
                <div class="carousel-item">
                    <img src="img_ad/ad3.png" class="d-block w-100" alt="Ad 3">
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>


    <div class="container mt-4">
        <div class="text-center section-title">
            <h5 class="fw-bold">บริการแนะนำ</h5>
        </div>


        <div class="container mt-4">
            <div class="row">
                <div class="col-6 col-sm-6 col-md-6 col-lg-4">
                    <a href="service_page.php" class="text-decoration-none">
                        <div class="card">
                            <img src="img_card/software.jpg" class="card-img-top" alt="Air Cleaning">
                            <div class="card-body">
                                <h5 class="card-title">ติดตั้งและแก้ไข Software</h5>

                                <p class="price">฿799</p>

                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-sm-6 col-md-6 col-lg-4">
                    <a href="service_page.php" class="text-decoration-none">
                        <div class="card">
                            <img src="img_card/hardware.jpg" class="card-img-top" alt="Carpet Cleaning">
                            <div class="card-body">
                                <h5 class="card-title">ซ่อมและอัปเกรด Hardware</h5>
                                <p class="price">฿1,299</p>
                            </div>
                        </div>
                    </a>
                </div>


            </div>
        </div>



        <div class="container mt-4">
            <div class="row">
                <div class="col-6 col-sm-6 col-md-6 col-lg-4">
                    <a href="service_page.php" class="text-decoration-none">
                        <div class="card">
                            <img src="img_card/network.jpg" class="card-img-top" alt="Air Cleaning">
                            <div class="card-body">
                                <h5 class="card-title">ดูแลและแก้ไข Network</h5>

                                <p class="price">฿1,499</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-sm-6 col-md-6 col-lg-4">
                    <a href="under.php" class="text-decoration-none">
                        <div class="card">
                            <img src="img_card/more.jpg" class="card-img-top" alt="Carpet Cleaning">
                            <div class="card-body">
                                <h5 class="card-title">ให้คำปรึกษาและแก้ปัญหา</h5>
                                <p class="price">฿2,499</p>
                            </div>
                        </div>
                    </a>
                </div>

            </div>
        </div>




        <!-- Footer -->
        <footer>
            <div class="d-flex justify-content-around">
                <a href="a_home.php">
                    <img src="icon/home.png" alt="Home" class="footer-icon">
                </a>
                <a href="under.php">
                    <img src="icon/card.png" alt="Cart" class="footer-icon">
                </a>
                <a href="transaction.php">
                    <img src="icon/transaction.png" alt="Order History" class="footer-icon">
                </a>
                <a href="under.php">
                    <img src="icon/more.png" alt="Contact" class="footer-icon">
                </a>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>