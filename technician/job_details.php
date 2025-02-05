<?php
session_start();
require_once '../db_connect.php';

// ตรวจสอบว่า user login หรือไม่
if (!isset($_SESSION['tech_id'])) {
    die("Unauthorized access.");
}

$tech_id = intval($_SESSION['tech_id']);

// Query เพื่อดึงข้อมูล request ล่าสุดที่เกี่ยวข้องกับ tech_id
$query = $conn->prepare("SELECT 
    sr.request_id, sr.description, sr.latitude, sr.longitude, sr.total_price, 
    sr.created_at, -- เพิ่มฟิลด์ created_at
    u.first_name, u.last_name, u.phone, 
    st.service_name
FROM service_requests sr
INNER JOIN users u ON sr.user_id = u.user_id
INNER JOIN service_types st ON sr.service_type_id = st.service_type_id
WHERE sr.tech_id = ?
ORDER BY sr.created_at DESC
LIMIT 1");
$query->bind_param("i", $tech_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die("No requests found for the technician.");
}

$data = $result->fetch_assoc();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://api.longdo.com/map/?key=8809a47dd3532ff420480af45e5a3e6f"></script>
    <script src="http://code.jquery.com/jquery-1.10.2.min.js"></script>


    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        * {
            font-family: 'Kanit', sans-serif;
        }

        body {
            background-color: rgb(255, 255, 255);
        }


        .map-container {
            height: 300px;

            overflow: hidden;
        }

        .footer-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .btn-success,
        .btn-danger {
            width: 48%;
        }

        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            padding: 30px;
            z-index: 1000;
            display: none;
            width: 90%;
            max-width: 500px;
        }

        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .btn-primary,
        .btn-secondary {
            width: 48%;
        }

        @media (max-width: 576px) {
            .footer-buttons {
                flex-direction: column;
                gap: 10px;
            }

            .btn-success,
            .btn-danger {
                width: 100%;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
            }
        }

        .profile-icon {
            width: 24px;
            /* กำหนดขนาดภาพให้เล็ก */
            height: 24px;
        }

        .mb-3.d-flex.align-items-center p {
            margin: 0;
        }

        .icon {
            width: 20px;
            height: 20px;
            vertical-align: middle;
        }

        .map-container {
            height: 200px;
            background-color: #f0f0f0;
            /* Placeholder background */
        }

        .card {
            border: none;
            /* ลบขอบ */
            box-shadow: none;
            /* ลบเงา */
        }


        .revenue-card {
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
            /* สีเทาอ่อน */
            border-radius: 8px;
            /* มุมโค้งมน */
            padding: 15px;
            /* เว้นระยะภายใน */
            margin-bottom: 15px;
            /* ระยะห่างด้านล่าง */
            font-size: 16px;
            /* ขนาดตัวอักษรทั่วไป */
            color: #343a40;
            /* สีข้อความ */
        }

        .revenue-card .icon {
            width: 40px;
            /* ขนาดไอคอน */
            height: 40px;
        }

        .revenue-content {
            display: flex;
            flex-direction: column;
            /* จัดเรียงในแนวตั้ง */
            align-items: center;
            /* จัดตำแหน่งกึ่งกลาง */
            margin-left: 10px;
            /* ระยะห่างจากไอคอน */
        }

        .revenue-content .total-price {
            font-size: 24px;
            /* ขนาดตัวอักษรใหญ่ */
            font-weight: bold;
            /* ตัวหนา */
            color: #00CC00;
            /* สีเขียว */
        }

        .revenue-content .revenue-label {
            font-size: 14px;
            /* ขนาดตัวอักษรเล็ก */
            color: #666666;
            /* สีเทา */
        }

        .revenue-left .total-price {
            color: #00CC00;
            /* สีเขียว */
        }

        .revenue-right .total-price {
            color: #333333;
            /* สีเทาเข้ม */
        }

        .revenue-left .revenue-label,
        .revenue-right .revenue-label {
            color: #666666;
            /* สีเทา */
        }

        .revenue-card {
            padding: 20px;
            /* ระยะห่างเท่ากันทั้ง 4 ด้าน */
            margin: 20px;
            /* ระยะห่างภายนอกเท่ากันทั้ง 4 ด้าน */
            border: 1px solid #eaeaea;
            /* เส้นขอบบางๆ เพื่อความสมดุล */
            border-radius: 10px;
            /* ขอบมนเล็กน้อย */
            background-color: #f9f9f9;
            /* สีพื้นหลังอ่อนเพื่อความโดดเด่น */
        }

        .revenue-content .total-price {
            font-size: 18px;
            font-weight: bold;
        }

        .revenue-content .revenue-label {
            font-size: 14px;
            color: #666;
        }

        .revenue-left .icon,
        .revenue-right .icon {
            width: 40px;
            height: 40px;
        }
    </style>
</head>

<body>

    <div class="card mb-3">
        <div class="map-container mb-3" id="map"></div>
        <div class="card-header bg-white text-primary fw-bold fs-5 d-flex justify-content-between align-items-center">
            <span>ปลายทาง</span>
            <button class="btn  btn-lg  rounded-pill transition-all hover:bg-danger hover:shadow-xl" onclick="openCancelPopup();">
                <img src="icon/i.png" alt="Cancel" style="width: 23px; height: 23px;">
            </button>


        </div>

        <div class="card-body">
            <p><img src="icon/profile.png" alt="Profile" class="icon me-2">
                <?= htmlspecialchars($data['first_name'] . ' ' . $data['last_name']); ?>, <?= htmlspecialchars($data['phone']); ?>
            </p>
            <p><img src="icon/location2.png" alt="Location" class="icon me-2">
                <span id="location-name">Loading...</span>
            </p>

        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card ">

                <div class="card mb-3">
                    <div class="card-header bg-white text-primary fw-bold fs-5">รายละเอียดคำสั่ง</div>

                    <div class="card-body">

                        <p><img src="icon/description.png" alt="Service Details" class="icon me-2"> <?= htmlspecialchars($data['service_name']); ?></p>
                        <p><strong>รายละเอียด</strong> <br><?= nl2br(htmlspecialchars($data['description'])); ?></p>



                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <p class="mb-0 text-muted" style="font-size: 0.9rem;">เลขคำสั่งรายการ</p>
                            <div class="d-flex align-items-center">
                                <p class="mb-0 text-muted me-2" style="font-size: 0.9rem;"><?php echo $data['request_id']; ?></p>
                                <button class="btn btn-link text-warning p-0" style="font-size: 0.9rem;" onclick="copyToClipboard('<?php echo $data['request_id']; ?>')">คัดลอก</button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <p class="mb-0 text-muted" style="font-size: 0.9rem;">เวลาทำรายการ</p>
                            <p class="mb-0 text-muted" style="font-size: 0.9rem;">
                                <?php
                                $timestamp = strtotime($data['created_at']);
                                $thai_months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                                $thai_date = date('j', $timestamp) . ' ' . $thai_months[date('n', $timestamp) - 1] . ' ' . (date('Y', $timestamp) + 543) . ', ' . date('H:i', $timestamp);
                                echo $thai_date;
                                ?>
                            </p>
                        </div>


                    </div>

                    <div class="revenue-card d-flex justify-content-between align-items-center p-3">
                        <!-- ข้อมูลชุดแรก -->
                        <div class="revenue-left d-flex align-items-center">
                            <img src="icon/revenue.png" alt="Revenue Icon" class="icon me-2">
                            <div class="revenue-content">
                                <span class="total-price"><?= number_format($data['total_price'], 2); ?> THB</span>
                                <span class="revenue-label"><strong>รายรับ</strong></span>
                            </div>
                        </div>

                        <!-- ข้อมูลชุดที่สอง -->
                        <div class="revenue-right d-flex align-items-center ms-4">
                            <img src="icon/payment.png" alt="Payment Icon" class="icon me-2">
                            <div class="revenue-content">
                                <span class="total-price">ตัดผ่านบัตร</span>
                                <span class="revenue-label"><strong>การชำระ</strong></span>
                            </div>
                        </div>
                    </div>





                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-center">
                            <button class="btn btn-success btn-lg shadow-lg rounded-pill transition-all hover:bg-success hover:shadow-xl" onclick="window.location.href='start_job.php';">
                                <i class="bi bi-play-circle-fill me-2"></i> เริ่มงาน
                            </button>
                        </div>
                    </div>
                </div>





            </div>
        </div>
    </div>


    <!-- Popup Overlay -->
    <div class="popup-overlay" id="popup-overlay"></div>

    <!-- Popup Modal -->
    <div class="popup" id="popup">
        <h3 class="text-center">ยกเลิกงาน</h3>
        <form id="cancel-form" method="POST" action="job_cancel.php">
            <div class="mb-3">
                <label for="reason" class="form-label text-danger">ยกเลิกเกิน 3 ครั้งมีผลต่อการถูกระงับการใช้งาน</label>
                <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="*กรุณาระบุสาเหตุ (มีผลต่อการพิจารณา)" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold text-uppercase">เลือกสาเหตุ</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="issue" id="issue1" value="ผู้ใช้บริการขอให้ยกเลิก" required>
                    <label class="form-check-label" for="issue1">ผู้ใช้บริการขอให้ยกเลิก</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="issue" id="issue2" value="มีปัญหาทางเทคนิค" required>
                    <label class="form-check-label" for="issue2">มีปัญหาทางเทคนิค</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="issue" id="issue3" value="อื่นๆ" required>
                    <label class="form-check-label" for="issue3">อื่นๆ</label>
                </div>
            </div>
            <div class="d-flex justify-content-between gap-3">
                <button type="submit" class="btn btn-primary">ตกลง</button>
                <button type="button" class="btn btn-secondary" onclick="closeCancelPopup();">ปิด</button>
            </div>
        </form>
    </div>

    <style>
        .text-danger {
            color: red;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-uppercase {
            text-transform: uppercase;
        }

        .gap-3>*:not(:last-child) {
            margin-right: 1rem;
        }
    </style>


    <script>
        function init() {
            const map = new longdo.Map({
                placeholder: document.getElementById('map')
            });

            const latitude = <?php echo $data['latitude']; ?>;
            const longitude = <?php echo $data['longitude']; ?>;

            map.location({
                lon: longitude,
                lat: latitude
            });

            map.Overlays.add(new longdo.Marker({
                lon: longitude,
                lat: latitude
            }));

            map.zoom(15);

            // Fetch location name using reverse geocoding
            $.ajax({
                url: "https://api.longdo.com/map/services/address",
                dataType: "json",
                type: "GET",
                data: {
                    key: "8809a47dd3532ff420480af45e5a3e6f",
                    lon: longitude,
                    lat: latitude
                },
                success: function(results) {
                    console.log(results);
                    if (results) {
                        document.getElementById('location-name').textContent = results.road || results.district || results.province || 'Unknown';
                    } else {
                        document.getElementById('location-name').textContent = 'Unknown';
                    }
                },
                error: function(response) {
                    console.error(response);
                    document.getElementById('location-name').textContent = 'Error fetching location';
                }
            });
        }

        function openCancelPopup() {
            document.getElementById('popup-overlay').style.display = 'block';
            document.getElementById('popup').style.display = 'block';
        }

        function closeCancelPopup() {
            document.getElementById('popup-overlay').style.display = 'none';
            document.getElementById('popup').style.display = 'none';
        }

        window.onload = init;
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
</body>

</html>