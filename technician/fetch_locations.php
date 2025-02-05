<?php
require_once '../db_connect.php';

$sql = "SELECT latitude, longitude FROM service_requests WHERE status = 'pending' AND confirm = 'yes'";
$result = $conn->query($sql);

$locations = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = [
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude']
        ];
    }
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($locations);
?>
