<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['tech_id'])) {
    die("Unauthorized access.");
}

$tech_id = intval($_SESSION['tech_id']);

// Update the status to "in_progress" for the specific request assigned to the current session technician
$query_update = $conn->prepare("UPDATE service_requests SET status = 'in_progress' WHERE tech_id = ? AND status = 'accepted'");
$query_update->bind_param("i", $tech_id);
$query_update->execute();

if ($query_update->affected_rows > 0) {
    // Status successfully updated
    $message = "Job status updated to in_progress.";
} else {
    // No rows affected, possibly already in progress or completed
    $message = "No job status updated. Check current status.";
}

// Query to get technician and customer coordinates
$query = $conn->prepare("SELECT 
    t.latitude AS tech_lat, t.longitude AS tech_lon,
    sr.latitude AS cust_lat, sr.longitude AS cust_lon,
    sr.request_id
FROM technicians t
INNER JOIN service_requests sr ON sr.tech_id = t.tech_id
WHERE t.tech_id = ?
ORDER BY sr.created_at DESC LIMIT 1");
$query->bind_param("i", $tech_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $tech_lat = $data['tech_lat'];
    $tech_lon = $data['tech_lon'];
    $cust_lat = $data['cust_lat'];
    $cust_lon = $data['cust_lon'];
    $request_id = $data['request_id'];
} else {
    die("No active requests found.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <title>Start Job</title>
    <style>
        * {

            font-family: 'Kanit', sans-serif;
        }

        html,
        body {
            margin: 0;
            height: 100%;
            overflow: hidden;
            position: relative;


        }

        #map {
            height: 100%;
            width: 100%;
            background: #e9ecef;
            position: relative;
        }

        #travel-box {
            position: absolute;
            top: 5%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #dee2e6;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 30px;
            padding: 20px;
            text-align: center;
            z-index: 1000;

        }

        #travel-box h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #495057;
            margin: 0;
        }

        #footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 17%;
            /* หรือปรับตามความต้องการ */
            background: #ffffff;
            /* โปร่งแสงเล็กน้อย */
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            border-top-left-radius: 50px;
            border-top-right-radius: 50px;
            z-index: 1000;
        }

        #timer {
            font-size: 2rem;
            font-weight: bold;
            color: green;
        }

        #arrived-btn {
            font-size: 1.2rem;
            padding: 10px 25px;
            border-radius: 25px;
        }

        h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #343a40;
        }

        p {
            font-size: 1.1rem;
            color: #6c757d;
        }
    </style>
    <script>
        // Prevent page refresh from affecting the job status
        window.addEventListener("beforeunload", function(e) {
            e.preventDefault();
            e.returnValue = ""; // This triggers a confirmation dialog
        });
    </script>
    <script src="https://api.longdo.com/map/?key=8809a47dd3532ff420480af45e5a3e6f"></script>
    <script>
        let startTime;
        let interval;

        function init() {
            const map = new longdo.Map({
                placeholder: document.getElementById('map')
            });

            const techLat = <?php echo $data['tech_lat']; ?>;
            const techLon = <?php echo $data['tech_lon']; ?>;
            const custLat = <?php echo $data['cust_lat']; ?>;
            const custLon = <?php echo $data['cust_lon']; ?>;

            map.Route.placeholder(document.createElement('div'));
            map.Route.add(new longdo.Marker({
                lon: techLon,
                lat: techLat
            }));
            map.Route.add(new longdo.Marker({
                lon: custLon,
                lat: custLat
            }));
            map.Route.search();

            // ตรวจสอบค่า startTime ใน Local Storage
            const savedStartTime = localStorage.getItem('startTime');
            if (savedStartTime) {
                startTime = parseInt(savedStartTime);
            } else {
                startTime = new Date().getTime();
                localStorage.setItem('startTime', startTime);
            }

            interval = setInterval(updateTimer, 1000);
        }

        function updateTimer() {
            const elapsed = Math.floor((new Date().getTime() - startTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;

            const timerElement = document.getElementById('timer');
            timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            if (minutes >= 60) {
                timerElement.style.color = 'red';
            } else if (minutes >= 30) {
                timerElement.style.color = 'orange';
            } else {
                timerElement.style.color = 'green';
            }
        }

        function arrived() {
            clearInterval(interval);

            const elapsed = Math.floor((new Date().getTime() - startTime) / 1000);

            // ล้างข้อมูลใน Local Storage
            localStorage.removeItem('startTime');

            fetch('save_time.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        request_id: <?php echo $request_id; ?>,
                        travel_time: elapsed
                    })
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    window.location.href = 'working.php';
                })
                .catch(error => console.error(error));
        }
    </script>
</head>

<body onload="init();">
    <div id="travel-box">
        <div class="spinner-grow text-primary" role="status" style="width: 1rem; height: 1rem; margin-bottom: 10px;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h2>กำลังเดินทาง</h2>
    </div>

    <div id="map" class="text-center">


        <p><?php echo $message; ?></p>
    </div>

    <div id="footer" class="d-flex justify-content-between align-items-center">
        <span id="timer">0:00</span>
        <button id="arrived-btn" class="btn btn-primary" onclick="arrived();">ถึงจุดหมายเเล้ว</button>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Disable refresh using F5 or Ctrl+R
        document.addEventListener('keydown', function(event) {
            if ((event.key === 'F5') || (event.ctrlKey && event.key === 'r')) {
                event.preventDefault(); // ป้องกันการรีเฟรช
                alert('ไม่สามารถรีเฟรชหน้าเว็บนี้ได้');
            }
        });

        // Disable refresh through browser's reload or navigation
        window.addEventListener('beforeunload', function(event) {
            event.preventDefault();
            // บางเบราว์เซอร์อาจต้องการ returnValue สำหรับข้อความแจ้งเตือน
            event.returnValue = '';
        });

        // Disable back button (history)
        window.history.pushState(null, '', window.location.href);
        window.onpopstate = function() {
            window.history.pushState(null, '', window.location.href);
        };
    </script>


</body>

</html>