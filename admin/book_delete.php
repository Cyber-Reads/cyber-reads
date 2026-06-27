<?php
session_start();
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("location: ../admin.php");
    exit;
}

require '../db_connection.php'; // [[11]]

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id > 0) {
    // ගොනු path ලබාගැනීම
    $result = mysqli_query($conn, "SELECT cover, pdf FROM books WHERE bookid = $book_id");
    if (mysqli_num_rows($result) > 0) {
        $book = mysqli_fetch_assoc($result);
        
        // සර්වර් එකෙන් ගොනු මකා දැමීම
        if (!empty($book['cover']) && file_exists('../' . $book['cover'])) {
            unlink('../' . $book['cover']);
        }
        if (!empty($book['pdf']) && file_exists('../' . $book['pdf'])) {
            unlink('../' . $book['pdf']);
        }
    }

    // සම්බන්ධතා (relations) මකා දැමීම
    mysqli_query($conn, "DELETE FROM books_has_author WHERE books_bookid = $book_id");
    mysqli_query($conn, "DELETE FROM books_has_category WHERE books_bookid = $book_id");

    // පොතේ වාර්තාව මකා දැමීම
    mysqli_query($conn, "DELETE FROM books WHERE bookid = $book_id");
}

mysqli_close($conn);
header("Location: dashboard.php?status=deleted");
exit();
?>