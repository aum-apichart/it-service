<?php
session_start();
require_once '../db_connect.php';

// ตรวจสอบค่า user_id ใน session
if (!isset($_SESSION['user_id'])) {
    echo "ไม่พบ user_id ใน session";
    exit;
}

$user_id = $_SESSION['user_id'];

// Query ข้อมูลคำขอตามสถานะ
$statusList = ['in_progress', 'completed', 'cancelled'];
$data = [];

foreach ($statusList as $status) {
    $query = $conn->prepare("SELECT sr.*, st.service_name, u.first_name, u.phone FROM service_requests sr JOIN service_types st ON sr.service_type_id = st.service_type_id JOIN users u ON sr.user_id = u.user_id WHERE sr.user_id = ? AND sr.status = ? ORDER BY sr.request_id DESC");
    $query->bind_param("is", $user_id, $status);
    $query->execute();
    $result = $query->get_result();
    $data[$status] = $result->fetch_all(MYSQLI_ASSOC);
}

// ดึงข้อมูลสถานะ 'accepted' และเพิ่มไปที่ 'in_progress'
$acceptedQuery = $conn->prepare("SELECT sr.*, st.service_name, u.first_name, u.phone FROM service_requests sr JOIN service_types st ON sr.service_type_id = st.service_type_id JOIN users u ON sr.user_id = u.user_id WHERE sr.user_id = ? AND sr.status = 'accepted' ORDER BY sr.request_id DESC");
$acceptedQuery->bind_param("i", $user_id);
$acceptedQuery->execute();
$acceptedResult = $acceptedQuery->get_result();
$data['in_progress'] = array_merge($acceptedResult->fetch_all(MYSQLI_ASSOC), $data['in_progress']);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการใช้บริการ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }

        .tab-content {
            text-align: left;
        }

        .nav-tabs {
            justify-content: center;
        }

        .card {
            position: relative;
            border-radius: 20px;
            min-height: 150px;
        }

        .total-price {
            position: absolute;
            top: 10px;
            right: 10px;
            font-weight: bold;
            color: #333;
            font-size: 20px;
        }

        footer {
            background-color: #f8f9fa;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100vw;
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
            margin: 0;
        }

        .footer-icon {
            width: 38px;
            height: 38px;
            object-fit: cover;
            margin-right: 0;
        }

        .icon {
            width: 20px;
            height: 20px;
            margin-right: 5px;
        }

        .btn-primary {
            position: absolute;
            bottom: 8px;
            right: 8px;
            border-radius: 12px;
            /* เลือกค่าที่เหมาะสมกับความโค้งมนของ card */
            padding: 8px 16px;
            /* เพิ่มระยะในปุ่มให้สัดส่วนดูสมดุล */
            font-size: 14px;
        }

        .green-text {
            color: #00CC00;
        }
        .dropdown-menu {
        background-color: #f8f9fa;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .dropdown-item {
        font-family: 'Kanit', sans-serif;
        font-size: 1rem;
        color: #333;
        padding: 10px 20px;
        transition: background-color 0.3s ease;
    }

    .dropdown-item:hover {
        background-color: #e9ecef;
    }

    .dropdown-toggle::after {
        display: none;
    }

    .dropdown-toggle {
        padding: 0;
    }
    </style>

</head>

<body class="bg-light">

    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a href="a_home.php" class="back-button">
                <i class="fas fa-chevron-left"></i>
            </a>
            <span class="navbar-brand mx-auto fw-bold">ประวัติการใช้บริการ</span>
            <div class="dropdown">
                <button class="btn btn-link dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="icon/filter.png" alt="Filter" style="width: 24px; height: 24px;">
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterDropdown">
                    <li><a class="dropdown-item" href="#" onclick="filterRequests(1)">อุปกรณ์ฮาร์ดเเวร์</a></li>
                    <li><a class="dropdown-item" href="#" onclick="filterRequests(2)">ติดตั้ง/เเก้ไขปัญหาซอฟเเวร์</a></li>
                    <li><a class="dropdown-item" href="#" onclick="filterRequests(3)">เเก้ไขปัญหาเน็ตเวิร์ค</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- แท็บ -->
        <ul class="nav nav-tabs mb-3 d-flex justify-content-center">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#in_progress">กำลังดำเนินการ</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#completed">เสร็จสิ้น</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#cancelled">ยกเลิก/ล้มเหลว</a>
            </li>
        </ul>

        <!-- เนื้อหาในแต่ละแท็บ -->
        <div class="tab-content">
            <?php foreach ($statusList as $status): ?>
                <div class="tab-pane fade <?php echo $status === 'in_progress' ? 'show active' : ''; ?>" id="<?php echo $status; ?>">
                    <?php if (!empty($data[$status])): ?>
                        <?php foreach ($data[$status] as $request): ?>
                            <div class="card mb-4 shadow-sm" data-service-type-id="<?php echo $request['service_type_id']; ?>">
                                <div class="card-body">
                                    <span class="total-price green-text">฿<?php echo number_format($request['total_price'], 2); ?></span>
                                    <h5 class="card-title text-primary" style="display: inline-block; font-size: 1.2em;">
                                        <?php
                                        if ($status === 'in_progress' && $request['status'] === 'accepted') {
                                            echo 'ยืนยันสำเร็จ';
                                        } else {
                                            $titles = [
                                                'in_progress' => 'กำลังดำเนินการ',
                                                'completed' => '<img src="icon/correct.png" alt="Completed Icon" style="width: 20px; height: 20px;">',
                                                'cancelled' => '<img src="icon/fail.png" alt="Completed Icon" style="width: 20px; height: 20px;">',
                                            ];
                                            echo $titles[$status];
                                        }
                                        ?>
                                    </h5>


                                    <!-- แสดงวันที่ในบรรทัดเดียวกัน -->
                                    <span style="font-size: 0.9em; color: #555; display: inline-block; margin-left: 10px;">
                                        <?php
                                        $createdAt = strtotime($request['created_at']);

                                        // แปลงชื่อเดือนเป็นภาษาไทยแบบย่อ
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

                                        $month = date('M', $createdAt); // เดือนในรูปแบบภาษาอังกฤษ
                                        $monthThai = $thaiMonths[$month]; // แปลงเดือนเป็นภาษาไทย

                                        echo date('j', $createdAt) . ' ' . $monthThai . ' ' . date('y, H:i', $createdAt); // '3 มี.ค. 68, 19:49'
                                        ?>
                                    </span>
                                    <!-- เส้นแบ่งข้อมูล (พร้อมเงาด้านล่าง) -->
                                    <hr style="border: 1px solidrgb(141, 141, 141); margin: 10px 0; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">


                                    <p class="card-text">
                                        <img src="icon/description.png" class="icon" alt="Service Icon">
                                        <span style="font-weight: bold; font-size: 1.1rem;"><?php echo $request['service_name']; ?></span>
                                    </p>

                                    <p class="card-text">
                                        <img src="icon/service.png" class="icon" alt="Detail Icon">

                                        <?php
                                        $description = $request['description'];
                                        $id = $request['request_id'];
                                        if (strlen($description) > 30) {
                                            echo substr($description, 0, 30) . '<span id="dots-' . $id . '">...</span>';
                                            echo '<span id="more-' . $id . '" style="display:none;">' . substr($description, 30) . '</span>';
                                        } else {
                                            echo $description;
                                        }
                                        ?>
                                    </p>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                                        <button class="btn btn-link p-0" id="btn-<?php echo $id; ?>" onclick="toggleReadMore(<?php echo $id; ?>)" style="font-size: 0.9em;">อ่านเพิ่มเติม</button>
                                        <a href="<?php
                                                    if ($status === 'in_progress' && in_array($request['status'], ['accepted', 'in_progress'])) {
                                                        echo 'detail.php?id=' . $request['request_id'];
                                                    } else {
                                                        echo 'transaction_detail.php?id=' . $request['request_id'];
                                                    }
                                                    ?>" class="btn btn-primary" style="border-radius: 12px;">ดูรายละเอียด</a>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">ไม่มีข้อมูลในขณะนี้</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        </div>
    </div>

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
</body>

</html>
<script>
    function toggleReadMore(id) {
        const moreText = document.getElementById('more-' + id);
        const dots = document.getElementById('dots-' + id);
        const btn = document.getElementById('btn-' + id);

        if (dots.style.display === 'none') {
            dots.style.display = 'inline';
            moreText.style.display = 'none';
            btn.textContent = 'อ่านเพิ่มเติม';
        } else {
            dots.style.display = 'none';
            moreText.style.display = 'inline';
            btn.textContent = 'ปิด';
        }
    }
</script>

<script>
    function filterRequests(serviceTypeId) {
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            const serviceType = card.getAttribute('data-service-type-id');
            if (serviceType == serviceTypeId || serviceTypeId == 0) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
</script>