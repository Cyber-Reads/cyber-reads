<?php

header('Content-Type: application/json; charset=utf-8');

include '../config/db_connection.php';

$sql = "SELECT categoryid, TRIM(category) AS category FROM category ORDER BY TRIM(category) ASC";

$result = $conn->query($sql);

if ($result === false) {
    error_log('Failed to fetch categories: ' . $conn->error);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load categories.']);
    $conn->close();
    exit();
}

$categories = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

echo json_encode($categories);

$conn->close();
?>
