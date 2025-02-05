<?php
require_once '../db_connect.php';

$techniciansQuery = $conn->prepare("
    SELECT 
        t.*,
        COALESCE(AVG(f.rating), 0) AS average_rating
    FROM technicians t
    LEFT JOIN service_requests sr ON t.tech_id = sr.tech_id
    LEFT JOIN feedback f ON sr.request_id = f.request_id
    WHERE t.status = 'online' AND t.work_status = 'Available'
    GROUP BY t.tech_id
");
$techniciansQuery->execute();
$techniciansResult = $techniciansQuery->get_result();

$technicians = [];
while ($row = $techniciansResult->fetch_assoc()) {
    $technicians[] = $row;
}

echo json_encode($technicians);
?>
