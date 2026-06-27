<?php

header('Content-Type: application/json');

include 'db_connection.php';

$sql = "SELECT categoryid, category FROM category ORDER BY category ASC";


$result = $conn -> query ($sql);

$categories = [] ;

if  ( $result->num_rows > 0 ) {

    while  ( $row = $result->fetch_assoc() ) {

        $categories[] = $row;


    }
}

echo json_encode ($categories);

$conn -> close();



?>