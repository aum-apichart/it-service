<?php
require_once '../db_connect.php';

// ดึงข้อมูลจากฐานข้อมูล
$query = "SELECT * FROM service_requests";
$result = $conn->query($query);

// ตรวจสอบข้อผิดพลาดของการ query
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Requests</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .update-button {
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        .update-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Service Requests</h1>
    <table>
        <thead>
            <tr>
                <th>Request ID</th>
                <th>User ID</th>
                <th>Service Type ID</th>
                <th>Description</th>
                <th>Distance</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Status</th>
                <th>Confirm</th>
                <th>Total Price</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Images</th>
                <th>Technician ID</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // ตรวจสอบว่ามีข้อมูลในผลลัพธ์หรือไม่
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['request_id'] . "</td>";
                    echo "<td>" . $row['user_id'] . "</td>";
                    echo "<td>" . $row['service_type_id'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                    echo "<td>" . $row['distance'] . "</td>";
                    echo "<td>" . $row['latitude'] . "</td>";
                    echo "<td>" . $row['longitude'] . "</td>";
                    echo "<td>" . $row['status'] . "</td>";
                    echo "<td>" . $row['confirm'] . "</td>";
                    echo "<td>" . $row['total_price'] . "</td>";
                    echo "<td>" . $row['created_at'] . "</td>";
                    echo "<td>" . $row['updated_at'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['images']) . "</td>";
                    echo "<td>" . $row['tech_id'] . "</td>";
                    echo "<td><button class=\"update-button\" onclick=\"resetToDefault(" . $row['request_id'] . ")\">Reset to Default</button></td>";
                    echo "</tr>";
                }
            } else {
                // หากไม่มีข้อมูลให้แสดงข้อความแจ้ง
                echo "<tr><td colspan='15'>No data available</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <script>
        // ฟังก์ชันสำหรับ Reset เฉพาะฟิลด์ status, confirm, และ tech_id
        function resetToDefault(requestId) {
            if (confirm('Are you sure you want to reset this entry to default values?')) {
                fetch('reset_service_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ request_id: requestId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('The record has been reset to default values.');
                        location.reload(); // รีโหลดหน้าเว็บเพื่อแสดงข้อมูลล่าสุด
                    } else {
                        alert('Failed to reset the record: ' + (data.error || 'Unknown error.'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while resetting the record.');
                });
            }
        }
    </script>
</body>
</html>
