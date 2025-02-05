<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['tech_id'])) {
    header('Location: login.php');
    exit;
}

$tech_id = $_SESSION['tech_id'];

// Handle Ajax request to update technician status and location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE technicians SET latitude = ?, longitude = ?, status = ? WHERE tech_id = ?");
    $stmt->bind_param("ddsi", $latitude, $longitude, $status, $tech_id);

    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

// Query เพื่อดึง service_requests ที่เกี่ยวข้องเมื่อ work_status เป็น 'Unavailable'
$requestQuery = $conn->prepare("
    SELECT sr.*, u.first_name AS first_name, u.phone AS phone
    FROM service_requests sr
    INNER JOIN technicians t ON sr.tech_id = t.tech_id
    INNER JOIN users u ON sr.user_id = u.user_id
    WHERE t.tech_id = ? AND t.work_status = 'Available'
    LIMIT 1
");
$requestQuery->bind_param("i", $tech_id);
$requestQuery->execute();
$requestResult = $requestQuery->get_result();
$requestData = $requestResult->fetch_assoc();

$isUnavailable = $requestData ? true : false;


// ตรวจสอบการเรียกด้วย AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_new_request') {
    $stmt = $conn->prepare("
    SELECT sr.first_name, sr.description, sr.phone, sr.request_id
    FROM service_requests sr
    INNER JOIN technicians t ON sr.tech_id = t.tech_id
    WHERE t.tech_id = ? AND sr.status = 'accepted'
    LIMIT 1
");

    $stmt->bind_param("i", $tech_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No new requests']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work - Technician</title>
    <script src="https://api.longdo.com/map/?key=8809a47dd3532ff420480af45e5a3e6f"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {

            font-family: 'Kanit', sans-serif;
        }

        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
        }

        #map {
            height: calc(100% - 60px);
            width: 100%;
        }

        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            height: 75px;
            background-color: #007bff;
            display: flex;
            justify-content: space-around;
            align-items: center;
            color: white;
        }

        .btn-footer {
            background-color: #224abe;
            color: white;
            border: none;
            border-radius: 30px;
            padding: 10px 20px;
            cursor: pointer;
            
        }

        .btn-footer:hover {
            background-color: #1a388e;
        }

        .btn-footer:disabled {
            background-color: gray;
            cursor: not-allowed;
        }

        .popup-notify {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 20px 40px;
            border-radius: 10px;
            text-align: center;
            display: none;
        }
    </style>
</head>

<body>
    <div id="map"></div>

    <a href="logout.php" class="btn btn-danger" style="position: absolute; top: 40px; right: 10px;">Logout</a>

    <div id="popup-notify" class="popup-notify">
        <h1>คุณกำลัง Online</h1>
        <p>กรุณาปิดสถานะก่อนออกจากหน้านี้</p>
    </div>

    <footer>
        <button id="back-btn" class="btn-footer"><i class="fas fa-chevron-left"></i></button>
        <button id="locate-btn" class="btn-footer">ค้นหาตำแหน่ง</button>
        <button id="status-btn" class="btn-footer" disabled>Offline</button>
    </footer>

    <script>
        let map, currentMarker = null,
            currentLatitude = null,
            currentLongitude = null,
            currentStatus = 'offline';

        function initMap() {
            map = new longdo.Map({
                placeholder: document.getElementById('map')
            });
            map.Ui.Geolocation.visible(true);
        }

        document.getElementById('locate-btn').addEventListener('click', () => {
            navigator.geolocation.getCurrentPosition((position) => {
                currentLatitude = position.coords.latitude;
                currentLongitude = position.coords.longitude;

                if (currentMarker) map.Overlays.remove(currentMarker);
                currentMarker = new longdo.Marker({
                    lon: currentLongitude,
                    lat: currentLatitude
                });
                map.Overlays.add(currentMarker);
                map.location({
                    lon: currentLongitude,
                    lat: currentLatitude
                }, true);
                document.getElementById('status-btn').disabled = false;
            }, (error) => alert('Error: ' + error.message));
        });

        document.getElementById('status-btn').addEventListener('click', () => {
    if (!currentLatitude || !currentLongitude) {
        alert('กรุณาปักหมุดก่อนเปลี่ยนสถานะ');
        return;
    }

    const button = document.getElementById('status-btn');
    const isOnline = button.textContent === 'Offline'; // ถ้าปัจจุบันเป็น Offline แล้วเปลี่ยนเป็น Online
    currentStatus = isOnline ? 'online' : 'offline';
    const workStatus = isOnline ? 'Available' : 'Unavailable'; // work_status ขึ้นอยู่กับ currentStatus

    // เปลี่ยน UI ของปุ่ม
    button.textContent = isOnline ? 'Online' : 'Offline';
    button.style.backgroundColor = isOnline ? '#28a745' : '#224abe';

    // ส่งข้อมูลไปอัปเดตสถานะในฐานข้อมูล
    fetch('update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tech_id: <?php echo json_encode($tech_id); ?>,
            latitude: currentLatitude,
            longitude: currentLongitude,
            status: currentStatus,
            work_status: workStatus // เพิ่ม work_status
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) alert('ไม่สามารถอัปเดตสถานะได้');
    });
});


        document.getElementById('back-btn').addEventListener('click', () => {
            if (currentStatus === 'online') {
                const popup = document.getElementById('popup-notify');
                popup.style.display = 'block';
                setTimeout(() => popup.style.display = 'none', 5000);
            } else {
                window.location.href = 'dashboard.php';
            }
        });

        window.addEventListener('beforeunload', () => {
            if (currentStatus === 'online') {
                fetch('update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        tech_id: <?php echo json_encode($tech_id); ?>,
                        latitude: currentLatitude,
                        longitude: currentLongitude,
                        status: 'offline'
                    })
                });
            }
        });

        initMap();
    </script>

    <script>
        let checkStatusInterval = setInterval(() => {
            fetch('check_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        tech_id: <?php echo json_encode($tech_id); ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.status === 'accepted') {
                        clearInterval(checkStatusInterval);
                        showPopup(data.data);
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 3000); // ตรวจสอบทุก 5 วินาที

        function showPopup(request) {
    const popup = document.createElement('div');
    popup.style.position = 'fixed';
    popup.style.top = '50%';
    popup.style.left = '50%';
    popup.style.transform = 'translate(-50%, -50%)';
    popup.style.background = 'rgba(0, 0, 0, 0.8)';
    popup.style.color = 'white';
    popup.style.padding = '20px';
    popup.style.borderRadius = '10px';
    popup.style.textAlign = 'center';

    popup.innerHTML = `
        <h2>มีงานใหม่เข้ามา</h2>
        <p>กดเพื่อดูรายละเอียด</p>
        <button id="accept-job-btn" style="padding: 10px 20px; background: green; color: white; border: none; border-radius: 5px; cursor: pointer;">
            รับงาน
        </button>
    `;

    document.body.appendChild(popup);

    document.getElementById('accept-job-btn').addEventListener('click', () => {
        // อัปเดทฟิลด์ status ของ technicians เป็น 'offline'
        fetch('update_technician_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tech_id: <?php echo json_encode($tech_id); ?>,
                status: 'offline',
            }),
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // ย้ายไปยังหน้ารายละเอียดงาน
                window.location.href = `job_details.php?request_id=${request.request_id}`;
            } else {
                alert('ไม่สามารถอัปเดตสถานะได้');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
        });
    });
}

    </script>
</body>

</html>