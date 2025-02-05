<?php
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$request_id = intval($_POST['request_id']);
$note = $conn->real_escape_string($_POST['note']);
$uploaded_images = [];

// Directory to save uploaded images
$upload_dir = '../uploads/working_images/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle image uploads
if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
    foreach ($_FILES['images']['name'] as $key => $name) {
        $tmp_name = $_FILES['images']['tmp_name'][$key];
        $file_name = time() . '_' . basename($name);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($tmp_name, $target_file)) {
            $uploaded_images[] = $file_name;
        }
    }
}

// Insert into working table
$query = $conn->prepare("INSERT INTO working (request_id, note, images) VALUES (?, ?, ?)");
$images_json = json_encode($uploaded_images);
$query->bind_param("iss", $request_id, $note, $images_json);

if ($query->execute()) {
    // Update the status in service_requests
    $update_request_status = $conn->prepare("UPDATE service_requests SET status = 'completed' WHERE request_id = ?");
    $update_request_status->bind_param("i", $request_id);
    $update_request_status->execute();

    // Update the work_status in technicians
    $update_technician_status = $conn->prepare("UPDATE technicians SET work_status = 'Available' WHERE tech_id = (SELECT tech_id FROM service_requests WHERE request_id = ?)");
    $update_technician_status->bind_param("i", $request_id);
    $update_technician_status->execute();

    echo 'Working details saved successfully!';
} else {
    http_response_code(500);
    echo 'Error saving working details.';
}
?>
