<?php

header('Content-Type: application/json; charset=utf-8');

include '../config/db_connection.php';

$bookId = 0;
if (isset($_GET['id']) && $_GET['id'] !== '') {
    $validatedId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($validatedId !== false && $validatedId > 0) {
        $bookId = $validatedId;
    }
}

if ($bookId > 0) {
    $sql = "SELECT 
                b.bookid, 
                b.title, 
                b.description, 
                b.cover, 
                b.pdf,
                GROUP_CONCAT(DISTINCT CONCAT(TRIM(a.authorfname), ' ', TRIM(a.authorlname)) SEPARATOR ', ') AS authors,
                GROUP_CONCAT(DISTINCT TRIM(c.category) SEPARATOR ', ') AS categories
            FROM books b
            LEFT JOIN books_has_author bha ON b.bookid = bha.books_bookid
            LEFT JOIN author a ON bha.author_authorid = a.authorid
            LEFT JOIN books_has_category bhc ON b.bookid = bhc.books_bookid
            LEFT JOIN category c ON bhc.category_categoryid = c.categoryid
            WHERE b.bookid = ?
            GROUP BY b.bookid";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Prepare failed in get_book_details.php: ' . $conn->error);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to load book details.']);
        $conn->close();
        exit();
    }

    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $book = $result->fetch_assoc();
        echo json_encode($book);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Book not found']);
    }

    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid book ID']);
}

$conn->close();
?>
