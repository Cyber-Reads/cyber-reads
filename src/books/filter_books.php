<?php

header('Content-Type: application/json; charset=utf-8');

include '../config/db_connection.php';

$json = file_get_contents('php://input');
$data = json_decode($json);

if ($json !== '' && $json !== false && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON request payload.']);
    $conn->close();
    exit();
}

$categoryIds = [];
if (isset($data->category_ids) && is_array($data->category_ids)) {
    foreach ($data->category_ids as $rawId) {
        $id = filter_var($rawId, FILTER_VALIDATE_INT);
        if ($id !== false && $id > 0) {
            $categoryIds[] = $id;
        }
    }
    $categoryIds = array_values(array_unique($categoryIds));
}

if (!empty($categoryIds)) {
    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));

    $sql = "SELECT b.bookid, b.title, b.cover,
                   GROUP_CONCAT(DISTINCT CONCAT(TRIM(a.authorfname), ' ', TRIM(a.authorlname)) SEPARATOR ', ') AS authors
            FROM books b
            LEFT JOIN books_has_author bha ON b.bookid = bha.books_bookid
            LEFT JOIN author a ON bha.author_authorid = a.authorid
            JOIN books_has_category bhc ON b.bookid = bhc.books_bookid
            WHERE bhc.category_categoryid IN ($placeholders)
            GROUP BY b.bookid
            ORDER BY b.bookid DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Prepare failed in filter_books.php: ' . $conn->error);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to filter books.']);
        $conn->close();
        exit();
    }

    $types = str_repeat('i', count($categoryIds));
    $stmt->bind_param($types, ...$categoryIds);
} else {
    $sql = "SELECT b.bookid, b.title, b.cover,
                   GROUP_CONCAT(DISTINCT CONCAT(TRIM(a.authorfname), ' ', TRIM(a.authorlname)) SEPARATOR ', ') AS authors
            FROM books b
            LEFT JOIN books_has_author bha ON b.bookid = bha.books_bookid
            LEFT JOIN author a ON bha.author_authorid = a.authorid
            GROUP BY b.bookid
            ORDER BY b.bookid DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Prepare failed in filter_books.php: ' . $conn->error);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to load books.']);
        $conn->close();
        exit();
    }
}

if (!$stmt->execute()) {
    error_log('Execute failed in filter_books.php: ' . $stmt->error);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load books.']);
    $stmt->close();
    $conn->close();
    exit();
}

$result = $stmt->get_result();
$books = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}

echo json_encode($books);

$stmt->close();
$conn->close();
?>
