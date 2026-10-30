<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php?status=error");
    exit;
}

$submitted_token = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submitted_token)) {
    header("Location: dashboard.php?status=error");
    exit;
}

require '../src/config/db_connection.php';

$book_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($book_id > 0) {
    $stmt = $conn->prepare("SELECT cover, pdf FROM books WHERE bookid = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $book = $result->fetch_assoc();
        $booksRoot = realpath(__DIR__ . '/../books');

        foreach (['cover', 'pdf'] as $field) {
            if (!empty($book[$field])) {
                $targetPath = realpath(__DIR__ . '/../' . ltrim($book[$field], '/\\'));
                if ($booksRoot && $targetPath && strpos($targetPath, $booksRoot) === 0 && is_file($targetPath)) {
                    @unlink($targetPath);
                }
            }
        }
    }
    $stmt->close();

    $del_auth = $conn->prepare("DELETE FROM books_has_author WHERE books_bookid = ?");
    $del_auth->bind_param("i", $book_id);
    $del_auth->execute();
    $del_auth->close();

    $del_cat = $conn->prepare("DELETE FROM books_has_category WHERE books_bookid = ?");
    $del_cat->bind_param("i", $book_id);
    $del_cat->execute();
    $del_cat->close();

    $del_book = $conn->prepare("DELETE FROM books WHERE bookid = ?");
    $del_book->bind_param("i", $book_id);
    $del_book->execute();
    $del_book->close();
}

mysqli_close($conn);
header("Location: dashboard.php?status=deleted");
exit();
?>
