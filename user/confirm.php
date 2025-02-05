<?php
session_start();
require_once '../db_connect.php';

// ตรวจสอบค่า user_id ใน session
if (!isset($_SESSION['user_id'])) {
    echo "ไม่พบ user_id ใน session";
    exit;
}

$user_id = $_SESSION['user_id'];

// Query ข้อมูลคำขอล่าสุดจาก service_requests
$requestQuery = $conn->prepare("SELECT sr.*, st.service_name, u.first_name, u.phone 
    FROM service_requests sr
    JOIN service_types st ON sr.service_type_id = st.service_type_id
    JOIN users u ON sr.user_id = u.user_id
    WHERE sr.user_id = ? 
    ORDER BY sr.request_id DESC LIMIT 1");
$requestQuery->bind_param("i", $user_id);
$requestQuery->execute();
$requestResult = $requestQuery->get_result();
$requestData = $requestResult->fetch_assoc();

if (!$requestData) {
    echo "ไม่พบข้อมูลคำขอของคุณ";
    exit;
}

$latitude = $requestData['latitude'];
$longitude = $requestData['longitude'];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://api.longdo.com/map/?key=8809a47dd3532ff420480af45e5a3e6f"></script>
    <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: rgb(255, 255, 255);
        }

        .card {
            box-shadow: none;
            border: none;
            margin-bottom: 0;
        }

        .card-body {
            padding-bottom: 0;
        }

        .total-price {
            font-weight: bold;
            color: #333;
            font-size: 20px;
        }

        footer {
            background-color: #f8f9fa;
            text-align: center;
            padding: 15px 0;
            box-shadow: none;
        }
        .custom-footer {
        height: 80px; /* ปรับความสูงตามต้องการ */
  
        padding: 5px 15px; /* ลดระยะห่างภายใน */
    }

    .custom-footer #confirmButton {
        margin: 0; /* ลดระยะห่างของปุ่ม */
    }

        .btn-primary {
            border-radius: 12px;
        }

        .text-primary {
            color: #007bff !important;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-primary"><i class="bi bi-geo-alt-fill"></i> สถานที่ใช้บริการ</h5>
                <p id="address">พิกัด: <?php echo $latitude; ?>, <?php echo $longitude; ?></p>
                <p class="d-flex align-items-center">
                    <img src="icon/profile.png" alt="Profile" style="width: 24px; height: 24px; margin-right: 8px;">
                    <?php echo $requestData['first_name']; ?>, 
                    <?php echo $requestData['phone']; ?>
                </p>
            </div>
        </div>
        <hr style="border: 1px solidrgb(141, 141, 141); margin: 10px 0; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-primary"><i class="bi bi-receipt-cutoff"></i> รายละเอียดบริการ</h5>
                <p><strong>บริการ</strong> <span class="float-end"><?php echo $requestData['service_name']; ?></span></p>
                <p><strong>รายละเอียด</strong> <br><?php echo $requestData['description']; ?></p>

                <div class="d-flex justify-content-end align-items-center">
                    <p class="fw-bold fs-4 text-primary">฿<?php echo number_format($requestData['total_price'], 2); ?></p>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <p class="mb-0 text-muted" style="font-size: 0.9rem;">เลขคำสั่งรายการ</p>
                    <div class="d-flex align-items-center">
                        <p class="mb-0 text-muted me-2" style="font-size: 0.9rem;"><?php echo $requestData['request_id']; ?></p>
                        <button class="btn btn-link text-warning p-0" style="font-size: 0.9rem;" onclick="copyToClipboard('<?php echo $requestData['request_id']; ?>')">คัดลอก</button>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <p class="mb-0 text-muted" style="font-size: 0.9rem;">เวลาทำรายการ</p>
                    <p class="mb-0 text-muted" style="font-size: 0.9rem;">
                        <?php
                        $createdAt = strtotime($requestData['created_at']);
                        $thaiMonths = [
                            'Jan' => 'ม.ค.',
                            'Feb' => 'ก.พ.',
                            'Mar' => 'มี.ค.',
                            'Apr' => 'เม.ย.',
                            'May' => 'พ.ค.',
                            'Jun' => 'มิ.ย.',
                            'Jul' => 'ก.ค.',
                            'Aug' => 'ส.ค.',
                            'Sep' => 'ก.ย.',
                            'Oct' => 'ต.ค.',
                            'Nov' => 'พ.ย.',
                            'Dec' => 'ธ.ค.'
                        ];
                        $month = date('M', $createdAt);
                        $monthThai = $thaiMonths[$month];
                        echo date('j', $createdAt) . ' ' . $monthThai . ' ' . date('y, H:i', $createdAt);
                        ?>
                    </p>
                </div>
                <label for="coupon" class="form-label"></label>
                <input type="text" class="form-control" id="coupon" placeholder="กรอกรหัสคูปอง">
            </div>
        </div>
    </div>
    <div class="py-3 px-4" style="margin: 10px 0; border-radius: 10px;">
        <a href="a_home.php" class="btn btn-danger w-100" style="max-width: 400px; margin: 0 auto; display: block;">ยกเลิกรายการ</a>
    </div>

    <footer class="fixed-bottom bg-white border-top custom-footer">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="javascript:history.back()" class="btn btn-link text-muted">
            <i class="bi bi-arrow-left-short" style="font-size: 35px;"></i>
        </a>
        <button class="btn btn-primary btn-lg px-4" id="confirmButton">
            <i class="fas fa-check-circle me-2"></i>ยืนยัน
        </button>
    </div>
</footer>


    <script>
        const latitude = <?php echo $latitude; ?>;
        const longitude = <?php echo $longitude; ?>;

        function fetchAddress() {
            $.ajax({
                url: "https://api.longdo.com/map/services/address",
                dataType: "json",
                type: "GET",
                data: {
                    key: "8809a47dd3532ff420480af45e5a3e6f",
                    lon: longitude,
                    lat: latitude
                },
                success: function(response) {
                    document.getElementById('address').innerText = response.road + ', ' + response.subdistrict + ', ' + response.district + ', ' + response.province;
                },
                error: function() {
                    document.getElementById('address').innerText = `พิกัด: ${latitude}, ${longitude}`;
                }
            });
        }

        document.addEventListener('DOMContentLoaded', fetchAddress);
    </script>

<script>
        document.getElementById('confirmButton').addEventListener('click', function() {
            if (confirm('คุณต้องการยืนยันคำขอนี้ใช่หรือไม่?')) {
                fetch('confirm_update.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            request_id: <?php echo $requestData['request_id']; ?>
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('การยืนยันเสร็จสิ้น!');
                            // ส่ง request_id ผ่าน URL
                            window.location.href = 'technician_online.php?request_id=<?php echo $requestData['request_id']; ?>';
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    </script>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('คัดลอกสำเร็จ: ' + text);
            }).catch(err => {
                alert('เกิดข้อผิดพลาดในการคัดลอก');
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>