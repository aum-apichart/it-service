<?php
session_start();
require_once '../db_connect.php';

// ตรวจสอบ request_id จาก URL
if (!isset($_GET['request_id'])) {
    echo "ไม่พบ request_id";
    exit;
}

$request_id = $_GET['request_id'];



// Query ข้อมูลคำขอจาก service_requests
$requestQuery = $conn->prepare("SELECT latitude, longitude FROM service_requests WHERE request_id = ?");
$requestQuery->bind_param("i", $request_id);
$requestQuery->execute();
$requestResult = $requestQuery->get_result();
$requestData = $requestResult->fetch_assoc();

if (!$requestData) {
    echo "ไม่พบข้อมูลคำขอ";
    exit;
}

$service_latitude = $requestData['latitude'];
$service_longitude = $requestData['longitude'];

// Query ช่างที่ออนไลน์และ work_status เป็น Available
$techniciansQuery = $conn->prepare("SELECT * FROM technicians WHERE status = 'online' AND work_status = 'Available'");

function renderStars($averageRating)
{
    $fullStars = floor($averageRating); // จำนวนดาวเต็ม
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $fullStars) {
            $html .= '<i class="fas fa-star text-warning"></i>'; // ดาวเต็ม
        } else {
            $html .= '<i class="far fa-star text-warning"></i>'; // ดาวว่าง
        }
    }
    return $html;
}

// Query ช่างที่ออนไลน์พร้อมดึงค่าเฉลี่ยดาว
$techniciansQuery = $conn->prepare("
    SELECT t.*, 
           COALESCE(AVG(f.rating), 0) AS average_rating 
    FROM technicians t
    LEFT JOIN service_requests sr ON t.tech_id = sr.tech_id
    LEFT JOIN feedback f ON sr.request_id = f.request_id
    WHERE t.status = 'online' AND t.work_status = 'Available'
    GROUP BY t.tech_id
");


$techniciansQuery->execute();
$techniciansResult = $techniciansQuery->get_result();

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technicians Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://api.longdo.com/map/?key=8809a47dd3532ff420480af45e5a3e6f"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }

        /* ปรับปรุงการจัดตำแหน่งของจุดสีเขียวที่มุมขวาบน */
        .green-dot {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 15px;
            height: 15px;
            background-color: #99FF00;
            border-radius: 50%;
        }

        .card {
            position: relative;
            /* ต้องใช้เพื่อให้จุดสีเขียวอยู่บนการ์ด */
        }



        .large-heading {
            font-size: 1.8rem;
            /* หรือขนาดที่คุณต้องการ */
            font-weight: bold;
            /* ตัวหนาถ้าต้องการ */
            line-height: 1.2;
            /* ความสูงระหว่างบรรทัด */
        }
        footer {
    background-color: #f8f9fa; /* พื้นหลังสีเทาอ่อน */
    text-align: center; /* จัดข้อความให้อยู่ตรงกลาง */
    padding: 15px 0; /* ระยะห่างด้านบนและล่าง */
    box-shadow: none; /* ไม่มีเงา */
}

.custom-footer {
    height: 80px; /* ความสูงของ footer */
    padding: 5px 15px; /* ลดระยะห่างภายใน */
    background-color: #f8f9fa; /* ใช้สีพื้นหลังเดียวกัน */
    display: flex;
    align-items: center; /* จัดให้เนื้อหาอยู่กลางแนวตั้ง */
    justify-content: space-between; /* แบ่งพื้นที่ระหว่างองค์ประกอบ */
    border-top: 1px solid #dee2e6; /* เส้นขอบด้านบน */
    position: fixed; /* ติดอยู่ด้านล่าง */
    bottom: 0;
    width: 100%;
    z-index: 1000;
}



.custom-footer .position-absolute.start-0 {
    left: 15px; /* ระยะห่างจากด้านซ้าย */
}

.custom-footer .text-center {
    font-weight: bold; /* ข้อความตัวหนา */
    color:rgb(0, 0, 0); /* สีข้อความ */
}

        

    </style>
</head>

<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center mb-5 large-heading">เลือกช่างที่ออนไลน์</h1>

        <div id="technician-list" class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
            <?php while ($technician = $techniciansResult->fetch_assoc()) {
                $averageRating = $technician['average_rating'];
            ?>
                <div class="col">
                    <div class="card shadow-sm h-100 position-relative">
                        <!-- จุดสีเขียวที่มุมขวาบน -->
                        <div class="green-dot"></div>

                        <div class="card-body d-flex">
                            <div class="d-flex flex-column align-items-center me-3">
                                <img src="icon/profile.png" alt="Icon" style="width: 40px; height: 40px;">
                            </div>
                            <div class="d-flex flex-column">
                                <h5 class="card-title mb-2" style="font-size: 1.2rem; font-weight: bold;">
                                    <?php echo htmlspecialchars($technician['first_name'] . ' ' . $technician['last_name']); ?>
                                </h5>
                                <p class="card-text mb-2 d-flex align-items-center">
                                    <img src="icon/call.png" alt="Call Icon" style="width: 20px; height: 20px; margin-right: 8px;">
                                    <?php echo htmlspecialchars($technician['phone']); ?>
                                </p>
                                <p id="distance-<?php echo $technician['tech_id']; ?>" class="text-muted">
                                    กำลังคำนวณระยะทาง...
                                </p>
                            </div>
                        </div>

                        <!-- ปุ่มยืนยันและแสดงดาว -->
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <div>
                                <?php echo renderStars($averageRating); ?>
                            </div>
                            <button class="btn btn-primary" onclick="confirmTechnician(<?php echo $technician['tech_id']; ?>)">
                                <i class="bi bi-check-circle"></i> ยืนยัน
                            </button>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

    </div>
    <footer class="fixed-bottom bg-white border-top custom-footer">
    <div class="container d-flex justify-content-between align-items-center position-relative">
        <!-- Back Button -->
        <a href="javascript:history.back()" class="btn btn-link text-muted position-absolute start-0">
            <i class="bi bi-arrow-left-short" style="font-size: 35px;"></i>
        </a>
        
        <!-- Center Text -->
        <span class="text-center mx-auto fw-bold">ย้อนกลับ</span>
    </div>
</footer>



    <!-- เพิ่ม Bootstrap และ Icons -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
</body>

<script>
    // ฟังก์ชันสำหรับยืนยันช่าง
    function confirmTechnician(tech_id) {
        if (confirm('คุณต้องการเลือกช่างคนนี้ใช่หรือไม่?')) {
            fetch('technician_update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        request_id: <?php echo $request_id; ?>,
                        tech_id: tech_id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('การเลือกช่างสำเร็จ!');
                        window.location.href = 'detail.php?id=<?php echo $request_id; ?>';
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    }

    // ฟังก์ชันสำหรับอัปเดตข้อมูลช่างที่ออนไลน์
    function updateTechnicians() {
        fetch('technician_get_online.php')
            .then(response => response.json())
            .then(data => {
                const technicianList = document.getElementById('technician-list');
                technicianList.innerHTML = ''; // เคลียร์รายการก่อน

                data.forEach(technician => {
                    // แปลง average_rating เป็น HTML ดาว
                    const averageRating = technician.average_rating || 0; // ค่าเริ่มต้นถ้าไม่มีค่าเฉลี่ย
                    const fullStars = Math.floor(averageRating);
                    let starsHTML = '';

                    for (let i = 1; i <= 5; i++) {
                        if (i <= fullStars) {
                            starsHTML += '<i class="fas fa-star text-warning"></i>'; // ดาวเต็ม
                        } else {
                            starsHTML += '<i class="far fa-star text-warning"></i>'; // ดาวว่าง
                        }
                    }

                    technicianList.innerHTML += `
                    <div class="col">
                        <div class="card shadow-sm h-100 position-relative">
                            <!-- จุดสีเขียวที่มุมขวาบน -->
                            <div class="green-dot"></div>
                            
                            <div class="card-body d-flex">
                                <!-- ไอคอน profile.png -->
                                <div class="d-flex flex-column align-items-center me-3">
                                    <img src="icon/profile.png" alt="Icon" style="width: 40px; height: 40px;">
                                </div>

                                <!-- ข้อมูลช่าง -->
                                <div class="d-flex flex-column">
                                    <h5 class="card-title mb-2" style="font-size: 1.2rem; font-weight: bold;">
                                        ${technician.first_name} ${technician.last_name}
                                    </h5>
                                    <p class="card-text mb-2 d-flex align-items-center">
                                        <img src="icon/call.png" alt="Call Icon" style="width: 20px; height: 20px; margin-right: 8px;">
                                        ${technician.phone}
                                    </p>
                                    <p id="distance-${technician.tech_id}" class="text-muted">
                                        กำลังคำนวณระยะทาง...
                                    </p>
                                </div>
                            </div>

                            <!-- ปุ่มยืนยันและดาว -->
                            <div class="card-footer d-flex justify-content-between align-items-center">
                                <div>
                                    ${starsHTML}
                                </div>
                                <button class="btn btn-primary" onclick="confirmTechnician(${technician.tech_id})">
                                    <i class="bi bi-check-circle"></i> ยืนยัน
                                </button>
                            </div>
                        </div>
                        
                    </div>`;

                });

                // คำนวณระยะทางเมื่อข้อมูลอัปเดต
                calculateDistances(data);
            })
            .catch(error => console.error('Error fetching technicians:', error));
    }

    // ฟังก์ชันสำหรับคำนวณระยะทางระหว่าง service location และ technicians
    function calculateDistances(technicians) {
        technicians.forEach(technician => {
            const serviceLocation = {
                lon: <?php echo $service_longitude; ?>,
                lat: <?php echo $service_latitude; ?>
            };
            const technicianLocation = {
                lon: technician.longitude,
                lat: technician.latitude
            };

            const distance = calculateHaversine(serviceLocation, technicianLocation);
            document.getElementById(`distance-${technician.tech_id}`).innerText = `ห่างจากคุณ ${distance.toFixed(2)} กม.`;
        });
    }

    // ฟังก์ชันคำนวณ Haversine
    function calculateHaversine(loc1, loc2) {
        const R = 6371; // Earth's radius in km
        const dLat = (loc2.lat - loc1.lat) * (Math.PI / 180);
        const dLon = (loc2.lon - loc1.lon) * (Math.PI / 180);

        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(loc1.lat * (Math.PI / 180)) * Math.cos(loc2.lat * (Math.PI / 180)) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);

        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    // เรียกใช้ฟังก์ชัน updateTechnicians ทุก ๆ 3 วินาที
    setInterval(updateTechnicians, 3000);

    // คำนวณระยะทางเมื่อโหลดหน้าเว็บครั้งแรก
    updateTechnicians();
</script>

</body>

</html>