<?php
$servername = "caboose.proxy.rlwy.net";
$username = "root";
$password = "HNnCgEwKuFZFVDqtbtRyuxNIAtPlTnUD";
$dbname = "itservice";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

?>
