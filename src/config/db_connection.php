<?php

error_reporting( E_ALL);
ini_set( 'display_errors', 1);


$servername = "localhost" ;
$username = "root";

$password = "12345678" ;
$dbname = "cb";

$conn = new mysqli($servername, $username, $password, $dbname );

if ($conn->connect_error) {

    header('Content-Type: application/json');

    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();


}

$conn->set_charset("utf8");



?>
