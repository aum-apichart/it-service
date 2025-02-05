<?php
session_start();
require_once '../db_connect.php';

// ตรวจสอบว่าเป็นคำขอ AJAX
if (isset($_GET['check_status']) && $_GET['check_status'] == '1') {
    $request_id = $_SESSION['request_id'] ?? null;

    if (!$request_id) {
        echo json_encode(['error' => 'No request ID found.']);
        exit;
    }

    $query = "SELECT status FROM service_requests WHERE request_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['status' => $row['status']]);
    } else {
        echo json_encode(['error' => 'Request not found.']);
    }
    exit;
}

// ตรวจสอบ request_id จาก URL หรือ session
if (isset($_GET['id'])) {
    $request_id = $_GET['id'];
    $_SESSION['request_id'] = $request_id;
} elseif (isset($_SESSION['request_id'])) {
    $request_id = $_SESSION['request_id'];
} else {
    die('No service request specified.');
}

// ดึงข้อมูล request
$query = "
    SELECT sr.request_id, sr.description, sr.created_at, sr.status, sr.latitude, sr.longitude, sr.total_price,
           st.service_name, 
           u.first_name, u.last_name, u.phone 
    FROM service_requests sr
    JOIN service_types st ON sr.service_type_id = st.service_type_id
    JOIN users u ON sr.user_id = u.user_id
    WHERE sr.request_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $request_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();

if (!$request) {
    die('Invalid service request.');
}

// ดึงข้อมูลช่างเทคนิค
$tech_query = "
    SELECT t.tech_id, t.first_name, t.last_name, t.profile, t.phone
    FROM technicians t
    JOIN service_requests sr ON t.tech_id = sr.tech_id
    WHERE sr.request_id = ?
";

$tech_stmt = $conn->prepare($tech_query);
$tech_stmt->bind_param('i', $request_id);
$tech_stmt->execute();
$tech_result = $tech_stmt->get_result();
$technician = $tech_result->fetch_assoc();

if (!$technician) {
    $technician = ['first_name' => 'ไม่ทราบ', 'last_name' => '', 'profile' => 'icon/profile.png'];
}

$_SESSION['request_data'] = $request;
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Details</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        * {
            font-family: 'Kanit', sans-serif;
        }

        body {
            background-color: rgb(255, 255, 255);
            padding-bottom: 40px;

        }


        .timeline {
            position: relative;
            margin: 20px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            background-color: #007bff;
            color: #fff;
            font-size: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            animation: bounce 1.5s infinite;
        }

        .timeline-icon:nth-child(2) {
            animation-delay: 0.5s;
        }

        .timeline-icon:nth-child(3) {
            animation-delay: 1s;
        }

        .text-center {
            text-align: center;
        }

        .icon {
            width: 24px;
            height: 24px;
            margin-right: 8px;
            vertical-align: middle;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .map-container {
            height: 300px;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
        }

        .card {
            border: none;
            border-radius: 12px;


        }

        .card-header {
            background-color: #f8f9fa;
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

        .footer-icon {
            width: 38px;
            height: 38px;
            object-fit: cover;
            margin-right: 0;
            /* ลบการเว้นระยะเพิ่มเติม */
        }

        .tech-profile {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #fff;

            padding: 16px;
            border-radius: 8px;
        }

        .profile-info {
            display: flex;
            align-items: center;
        }

        .profile-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-text {
            margin-left: 16px;
            line-height: 1.2;
        }

        .profile-name,
        .profile-role {
            margin: 0;
        }

        .profile-role {
            font-size: 14px;
            color: #888;
        }


        .profile-arrow img {
            width: 20px;
            height: 20px;
            object-fit: contain;
        }
    </style>
    <script src="https://api.longdo.com/map/?key=8809a47dd3532ff420480af45e5a3e6f"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>

<body onload="initMap();">
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a href="transaction.php" class="back-button">
                <i class="fas fa-chevron-left"></i>
            </a>
            <span class="navbar-brand mx-auto fw-bold">รายละเอียด</span>
        </div>
    </nav>
    <div class="container mt-4">
        <div class="text-center mb-4" style="position: relative;">
            <h1 id="status-heading" class="fw-bold text-dark">กำลังดำเนินการ</h1>
            <p>จะถึงจุดหมายภายใน 45 นาที</p>

            <div class="timeline justify-content-center" style="border-bottom: 2px solid #ccc; padding-bottom: 10px;">
                <div class="timeline-icon"><i class="fa fa-user"></i></div>
                <div class="timeline-icon"><i class="fa fa-motorcycle"></i></div>
                <div class="timeline-icon"><i class="fa fa-check"></i></div>
            </div>

            <!-- กล่องช่างเทคนิค -->
            <div class="tech-profile">
                <!-- Profile Section -->
                <div class="profile-info">
                    <img src="icon/profile.png" alt="Technician Profile" class="profile-icon">
                    <div class="profile-text">
                        <p class="profile-name"><?= htmlspecialchars($technician['first_name']) . ' ' . htmlspecialchars($technician['last_name']) ?></p>
                        <p class="profile-role"><?= htmlspecialchars($technician['phone']) ?></p>

                    </div>
                </div>
                <!-- Arrow Icon -->
                <a href="technician_profile.php?id=<?= urlencode($technician['tech_id']) ?>" class="profile-arrow">
                    <img src="icon/right-arrow.png" alt="Go to profile">
                </a>
            </div>





        </div>

        <script>
            setInterval(() => {
                // ส่งคำขอ AJAX เพื่อดึงสถานะล่าสุด
                fetch('?check_status=1')
                    .then(response => response.json())
                    .then(data => {
                        const statusHeading = document.getElementById('status-heading');
                        if (data.status === 'in_progress') {
                            statusHeading.textContent = 'ผู้ซ่อมบำรุงกำลังเดินทาง';
                        } else if (data.status === 'maintain') {
                            statusHeading.textContent = 'กำลังซ่อมบำรุง';
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }, 3000); // ตรวจสอบทุก 3 วินาที
        </script>





        <div class="card mb-3">
            <div class="card-header bg-white text-primary fw-bold fs-5 d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#details-collapse" style="cursor: pointer;">
                รายละเอียดคำสั่ง
                <img src="icon/down.png" alt="Toggle Details" class="icon-toggle" style="width: 20px; transition: transform 0.3s;">
            </div>

            <div id="details-collapse" class="collapse">
                <div class="card-body">
                    <p><img src="icon/description.png" alt="Service Details" class="icon"> <?= $request['service_name'] ?></p>
                    <p><strong>รายละเอียด</strong> <?= $request['description'] ?></p>
                    <p class="text-center mb-0"><strong></strong>
                        <?php
                        $thai_months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                        $timestamp = strtotime($request['created_at']);
                        $thai_date = date('j', $timestamp) . ' ' . $thai_months[date('n', $timestamp) - 1] . ' ' . (date('Y', $timestamp) + 543) . ', ' . date('H:i', $timestamp);
                        echo $thai_date;
                        ?>
                    </p><br>
                    
                    <p class="text-center mb-0" style="font-weight: bold; font-size: 1.5rem; color: #007bff; ">
                        <?= number_format($request['total_price'], 2) ?> บาท
                    </p>
                    <p class="text-center" style="font-size: 0.9rem; color: #6c757d;">
                        ยอดเงินจะถูกชำระเมื่องานเสร็จสิ้น
                    </p>
                  
                </div>
            </div>

        </div>

        <div class="card mb-3">
            <div class="card-header bg-white text-primary fw-bold fs-5">ปลายทาง</div>


            <div class="card-body">
                <p><img src="icon/profile.png" alt="Profile" class="icon"> <?= $request['first_name'] . ' ' . $request['last_name'] ?>, <?= $request['phone'] ?></p>
                <p><img src="icon/location2.png" alt="Location" class="icon"> <span id="location-details">Loading...</span></p>
                <div id="map" class="map-container"></div>
            </div>
        </div>

    </div>
    <footer>
        <div class="d-flex justify-content-around">
            <a href="a_home.php">
                <img src="icon/home.png" alt="Home" class="footer-icon">
            </a>
            <a href="shopping_cart.php">
                <img src="icon/card.png" alt="Cart" class="footer-icon">
            </a>
            <a href="transaction.php">
                <img src="icon/transaction.png" alt="Order History" class="footer-icon">
            </a>
            <a href="contact_us.php">
                <img src="icon/more.png" alt="Contact" class="footer-icon">
            </a>
        </div>
    </footer>
</body>

</html>
<script>
    let map;

    function initMap() {
        map = new longdo.Map({
            placeholder: document.getElementById('map')
        });
        rerverseGeocoding(<?= $request['latitude'] ?>, <?= $request['longitude'] ?>);
    }

    function rerverseGeocoding(lat, lon) {
        $.ajax({
            url: "https://api.longdo.com/map/services/address",
            dataType: "json",
            type: "GET",
            data: {
                key: "8809a47dd3532ff420480af45e5a3e6f",
                lon: lon,
                lat: lat
            },
            success: function(results) {
                if (results) {
                    $('#location-details').text(results.road || results.district || results.province || 'Unknown');
                } else {
                    $('#location-details').text('Unknown');
                }
                map.location({
                    lat: lat,
                    lon: lon
                });
                map.Overlays.add(new longdo.Marker({
                    lat: lat,
                    lon: lon
                }));
            },
            error: function(response) {
                console.error('Error fetching location details:', response);
                $('#location-details').text('Error fetching location');
            }
        });
    }


    function checkStatus() {
        $.ajax({
            url: 'detail_update.php',
            method: 'POST',
            data: {
                request_id: <?= $request_id ?>
            },
            success: function(status) {
                if (status === 'cancelled') {
                    window.location.href = 'request_fail.php';
                } else if (status === 'completed') {
                    window.location.href = 'request_succeed.php';
                }
            },
            error: function(err) {
                console.error('Error checking status:', err);
            }
        });
    }

    setInterval(checkStatus, 2000);
</script>