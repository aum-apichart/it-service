<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['tech_id'])) {
    die("Access denied. Please login first.");
}

$tech_id = $_SESSION['tech_id'];

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT first_name, last_name, credit, level, latitude, longitude, request_count FROM technicians WHERE tech_id = ?";
$stmt = $conn->prepare($sql);

$first_name = "";
$last_name = "";
$credit = 0;
$level = "";
$latitude = null;
$longitude = null;
$request_count = 0;

if ($stmt) {
    $stmt->bind_param("i", $tech_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $first_name = htmlspecialchars($row['first_name']);
        $last_name = htmlspecialchars($row['last_name']);
        $credit = $row['credit'];
        $level = htmlspecialchars($row['level']);
        $latitude = $row['latitude'];
        $longitude = $row['longitude'];
        $request_count = $row['request_count'];
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://api.longdo.com/map/?key=8809a47dd3532ff420480af45e5a3e6f"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Kanit', sans-serif;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            padding-bottom: 80px;
        }

        .top-bar {
            background-color: rgb(56, 151, 252);
            padding: 10px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        #map {
            width: 100%;
            height: 862px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 6px;
            position: fixed;
            bottom: 100px;
            left: 0;
            right: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
            margin-bottom: 8px;
        }

        .stat-card {
            background: white;
            padding: 10px;
            border-radius: 12px;
            box-shadow: 0 5px 5px rgba(0, 0, 0, 0.35);
            position: relative;
        }



        .stat-card::after {
            content: '›';
            position: absolute;
            right: 15px;
            top: 0;
            text-align: right;
            font-size: 24px;
            color: #ccc;
        }

        .stat-label {
            color: #333;
            font-size: 15px;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 45px;
            font-weight: bold;
        }

        .stat-value.highlight {
            color: #00ff66;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: var(--footer-height, 95px);
            background-color: #ffffff;
            border-top: 2px solid #ddd;
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 20px 0;

        }

        :root {
            --footer-height: 95px;
        }

        .footer-item {
            text-align: center;
            font-size: 14px;
            color: #007bff;
        }

        .footer-item img {
            width: 35px;
            height: 35px;
            display: block;
            margin: 0 auto;
        }

        .footer-item span {
            display: block;
            margin-top: 5px;
        }

        .footer-item img[src="icon/start.png"] {
            width: 80px;
            height: 80px;
            margin-top: -47px;
        }

        .footer-item a {
            text-decoration: none;
        }

        .footer-item a:hover {
            text-decoration: none;
        }

        .loader-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 1000;
        }

        .progress-bar {
            width: 100%;
            max-width: 130px;
            height: 15px;
            background-color: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress {
            width: 0;
            height: 100%;
            background-color: #007bff;
            animation: fill 4s infinite;
        }

        @keyframes fill {
            0% {
                width: 0;
            }

            100% {
                width: 100%;
            }
        }

        .hide {
            display: none;
        }
    </style>

    <script>
        var map;

        document.addEventListener("DOMContentLoaded", function() {
            showLoader();
            setTimeout(function() {
                hideLoader();
                init();
            }, 1500); // Simulated loading time
        });

        function init() {
            map = new longdo.Map({
                placeholder: document.getElementById('map'),
                zoom: 12,
                location: {
                    lon: 100.5018,
                    lat: 13.7563
                }
            });
        }

        function showLoader() {
            document.querySelector('.loader-container').classList.remove('hide');
            document.querySelector('.top-bar').classList.add('hide');
            document.querySelector('.container').classList.add('hide');
        }

        function hideLoader() {
            document.querySelector('.loader-container').classList.add('hide');
            document.querySelector('.top-bar').classList.remove('hide');
            document.querySelector('.container').classList.remove('hide');
        }
    </script>
</head>

<body>
    <div class="loader-container">
        <div class="progress-bar">
            <div class="progress"></div>
        </div>
    </div>

    <div class="top-bar">
        <div style="font-size: 1.5rem; font-weight: bold; margin-left: 1px;">
            <img src="icon/logo2.png" alt="IT Service Logo" style="width: 140px; height: auto;">
        </div>
        <div style="display: flex; align-items: center;">
            <a href="under.php">
                <img src="icon/profile.png" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            </a>
        </div>
    </div>

    <div id="map"></div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label" style="font-weight: bold;">รายได้ทั้งหมด</div>
                <div class="stat-value highlight">฿<?= number_format($credit) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label" style="font-weight: bold;">งานทั้งหมด</div>
                <div class="stat-value"><?= $request_count ?></div>
            </div>



        </div>
        <div class="stat-card-buttom" style="width: 100%; margin: 0 auto; margin-bottom: 25px; background-color:rgb(255, 255, 255); 
        padding: 10px; border: 1px solid #ddd; border-radius: 12px; display: flex; align-items: center; box-shadow: 0 5px 5px rgba(0, 0, 0, 0.35);">
            <div style="text-align: center; margin: 20px 0; margin-left: 10px; margin-right: 15px;">
                
            <img src="icon/user.png" style="width: 24px; height: 24px; color: #333; margin: 0;" alt="User Icon">
            </div>
            <div style="flex: 3;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                <div class="stat-label" style="font-weight: bold; color: #333; font-size: 20px;">ระดับ</div>
                    <div class="stat-label" style="margin-bottom: 10px;">
                        <?= $level ?>
                    </div>
                </div>
                <div class="stat-value" style="font-size: 17px; color: #0088ff;"><?= $first_name . ' ' . $last_name ?></div>
            </div>
        </div>


        <footer class="footer">
            <div class="footer-item">
                <a href="dashboard.php">
                    <img src="icon/home.png" alt="Home">
                    <span>หน้าหลัก</span>
                </a>
            </div>
            <div class="footer-item">
                <a href="wallet.php">
                    <img src="icon/wallet2.png" alt="Wallet">
                    <span>กระเป๋า</span>
                </a>
            </div>
            <div class="footer-item">
                <a href="work.php">
                    <img src="icon/start.png" alt="Start">
                    <span>เริ่มงาน</span>
                </a>
            </div>
            <div class="footer-item">
                <a href="under.php">
                    <img src="icon/history.png" alt="History">
                    <span>ประวัติ</span>
                </a>
            </div>
            <div class="footer-item">
                <a href="under.php">
                    <img src="icon/help.png" alt="Help">
                    <span>ช่วยเหลือ</span>
                </a>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>