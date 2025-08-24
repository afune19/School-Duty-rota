<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "duty_rota";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
